<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use ControleOnline\Entity\User;
use ControleOnline\Repository\ExtraDataRepository;
use ControleOnline\Repository\ExtraFieldsRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

class ExtraDataService
{
    private static $persisted = false;
    private $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack $requestStack,
        private Security $security,
        private DeviceService $deviceService,
        private SkyNetService $skyNetService

    ) {
        $this->request = $requestStack->getCurrentRequest();
    }


    public function getByExtraFieldByEntity(ExtraFields $extraFields, object $entity)
    {
        $class = $this->getEntityName($entity);
        return $this->extraDataRepository()->findOneByExtraFieldsEntityNameEntityId(
            $extraFields,
            $class->getShortName(),
            $entity->getId()
        );
    }

    public function getExtraDataFromEntity(object $entity)
    {
        $class = $this->getEntityName($entity);
        return $this->extraDataRepository()->findByEntityNameEntityId(
            $class->getShortName(),
            $entity->getId()
        );
    }
    

    public function getEntityByExtraData(string $context, string $fieldName, string $code, object|string $entity)
    {
        $class = $this->getEntityName($entity);
        $context = trim($context);
        $fieldName = trim($fieldName);
        $code = trim($code);

        if ($context === '' || $fieldName === '' || $code === '') {
            return null;
        }

        if (is_object($entity)) {
            $entityId = method_exists($entity, 'getId') ? (int) $entity->getId() : 0;
            if ($entityId <= 0) {
                return null;
            }
        }

        $extraFields = $this->discoveryExtraFields($fieldName, $context, '{}');
        if (!$extraFields instanceof ExtraFields) {
            return null;
        }

        $extraData = $this->extraDataRepository()->findOneByExtraFieldsEntityNameValue(
            $extraFields,
            $class->getShortName(),
            $code
        );

        if (!$extraData) {
            return null;
        }

        try {
            return $this->manager->getRepository($class->getName())->find($extraData->getEntityId());
        } catch (\ReflectionException | \Doctrine\Persistence\Mapping\MappingException | \Doctrine\ORM\Mapping\MappingException $exception) {
            // Stale Doctrine metadata (e.g. Document::$vehicle) must not 500 webhooks.
            return null;
        }
    }

    public function discoveryExtraData(object $entity, string $context, string $fieldName, string $code, ?string $source = null)
    {
        $class = $this->getEntityName($entity);
        $entityId = (int) $entity->getId();
        if ($entityId <= 0) {
            return $entity;
        }

        $normalizedCode = $this->normalizeExtraDataValue($code);
        if ($normalizedCode === '') {
            return $entity;
        }

        $this->upsertExtraDataValue(
            $context,
            $class->getShortName(),
            $entityId,
            $fieldName,
            $normalizedCode,
            'text',
            $source
        );

        $managedEntity = $this->manager->getRepository($class->getName())->find($entityId);

        return $managedEntity ?? $entity;
    }

    public function discoveryExtraFields(string $fieldName, string $context, ?string $configs = '{}', ?string $fieldType = 'text', ?bool $required = false): ExtraFields
    {
        $extraFields = $this->extraFieldsRepository()->findOneByContextNameType(
            $context,
            $fieldName,
            $fieldType ?? 'text'
        );

        if (!$extraFields) {
            $extraFields = new ExtraFields();
            $extraFields->setName($fieldName);
            $extraFields->setContext($context);
            $extraFields->setConfigs($configs);
            $extraFields->setType($fieldType);
            $extraFields->setRequired($required);
            $this->manager->persist($extraFields);
            $this->manager->flush();
        }

        return $extraFields;
    }

    public function getExtraDataValue(
        string $context,
        string $entityName,
        int $entityId,
        string $fieldName = 'code',
        string $fieldType = 'text'
    ): ?string {
        if ($entityId <= 0) {
            return null;
        }

        $context = trim($context);
        $entityName = trim($entityName);
        $fieldName = trim($fieldName);

        if ($context === '' || $entityName === '' || $fieldName === '') {
            return null;
        }

        $extraFields = $this->extraFieldsRepository()->findOneByContextNameType(
            $context,
            $fieldName,
            $fieldType
        );

        if (!$extraFields instanceof ExtraFields) {
            return null;
        }

        $extraData = $this->extraDataRepository()->findOneByExtraFieldsEntityNameEntityId(
            $extraFields,
            $entityName,
            $entityId
        );

        if (!$extraData instanceof ExtraData) {
            return null;
        }

        $value = trim((string) $extraData->getValue());

        return $value !== '' ? $value : null;
    }

    public function upsertExtraDataValue(
        string $context,
        string $entityName,
        int $entityId,
        string $fieldName,
        mixed $value,
        string $fieldType = 'text',
        ?string $source = null
    ): void {
        $context = trim($context);
        $entityName = trim($entityName);
        $fieldName = trim($fieldName);
        if ($entityId <= 0) {
            return;
        }

        $normalizedValue = $this->normalizeExtraDataValue($value);
        if ($context === '' || $entityName === '' || $fieldName === '' || $normalizedValue === '') {
            return;
        }

        $extraFields = $this->discoveryExtraFields($fieldName, $context, '{}', $fieldType);
        $normalizedSource = $this->normalizeExtraDataSource($source);

        $this->extraDataRepository()->upsertValue(
            $extraFields,
            $entityName,
            $entityId,
            $normalizedValue,
            $normalizedSource
        );
    }

    private function getUserIp()
    {
        return $this->request?->getClientIp();
    }

    public function discoveryDevice(&$entity)
    {
        if (
            $entity instanceof Device
            || $entity instanceof DeviceConfig
            || !$this->request
            || !$this->request->headers
        ) {
            return;
        }

        $deviceId = $this->request->headers->get('DEVICE') ?: $this->getUserIp();
        if (method_exists($entity, 'setDevice')) {
            if ($entity->getDevice()) return;
            $device = $this->deviceService->discoveryDevice($deviceId);
            $entity->setDevice($device);
        }
    }

    private function resolveManagedUser(?User $user): ?User
    {
        if (!$user instanceof User) {
            return null;
        }

        if ($this->manager->contains($user)) {
            return $user;
        }

        $userId = $user->getId();
        if (is_int($userId) && $userId > 0) {
            $managedUser = $this->manager->getRepository(User::class)->find($userId);
            if ($managedUser instanceof User) {
                return $managedUser;
            }
        }

        $username = trim((string) $user->getUsername());
        if ($username !== '') {
            $managedUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($managedUser instanceof User) {
                return $managedUser;
            }
        }

        return null;
    }

    public function discoveryUser(&$entity)
    {
        $token = $this->security->getToken();
        $user = $token ? $token->getUser() : null;

        if (!$user instanceof User) {
            $botUser = $this->skyNetService->getBotUser();
            if (!$botUser instanceof User) {
                $this->skyNetService->discoveryBotUser();
                $botUser = $this->skyNetService->getBotUser();
            }

            $user = $botUser;
        }

        $managedUser = $this->resolveManagedUser($user);

        if ($managedUser instanceof User && method_exists($entity, 'setUser') && !$entity->getUser()) {
            $entity->setUser($managedUser);
        }
    }

    public function persist(&$entity)
    {
        if (self::$persisted == true)
            return;
        self::$persisted = true;

        //$this->manager->persist($entity);
        //$this->manager->flush();
        $this->persistData($entity);
    }

    private function getEntityName(object | string $entity): \ReflectionClass
    {
        return (new \ReflectionClass($entity));
    }

    /**
     * @return ExtraFieldsRepository
     */
    private function extraFieldsRepository(): ExtraFieldsRepository
    {
        return $this->manager->getRepository(ExtraFields::class);
    }

    /**
     * @return ExtraDataRepository
     */
    private function extraDataRepository(): ExtraDataRepository
    {
        return $this->manager->getRepository(ExtraData::class);
    }

    private function persistData(&$entity = null)
    {

        if ($entity) {
            $entity_id = $entity->getId();
            $entity_name = $this->getEntityName($entity)->getShortName();

            //$this->manager->persist($entity);
        } else {
            $json =       json_decode($this->request->getContent(), true);
            $extra_data = isset($json['extra-data']) ? $json['extra-data'] : null;
            if (!$extra_data)
                return;
            $entity_id = $extra_data['entity_id'];
            $entity_name = $extra_data['entity_name'];
            $source = $this->normalizeExtraDataSource($extra_data['source'] ?? $json['source'] ?? null);
        }


        if (!$entity_id || !$entity_name)
            return;

        if (!isset($extra_data) || !isset($extra_data['data']))
            return;

        foreach ($extra_data['data'] as $key => $data) {
            $extra_fields = $this->manager->getRepository(ExtraFields::class)->find($key);
            if (!$extra_fields instanceof ExtraFields) {
                continue;
            }

            $normalizedData = $this->normalizeExtraDataValue($data);
            if ($normalizedData === '') {
                continue;
            }

            $extraData = $this->extraDataRepository()->findOneByExtraFieldsEntityNameEntityId(
                $extra_fields,
                $entity_name,
                $entity_id
            );

            if (!$extraData)
                $extraData = new ExtraData();

            $extraData->setExtraFields($extra_fields);
            $extraData->setEntityName($entity_name);
            $extraData->setEntityId($entity_id);
            $extraData->setValue($normalizedData);
            if (isset($source) && $source !== null) {
                $extraData->setSource($source);
            }
            $this->manager->persist($extraData);
        }


        $this->manager->flush();
    }

    public function  noChange()
    {
        $this->persistData();
    }

    private function normalizeExtraDataValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            $normalized = trim($value);

            return $normalized !== '' ? $normalized : '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function normalizeExtraDataSource(mixed $source): ?string
    {
        $normalized = trim((string) $source);

        return $normalized !== '' ? $normalized : null;
    }
}
