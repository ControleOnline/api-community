<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Entity\People;
use App\Entity\Particulars;
use App\Entity\Person;
use App\Entity\File as File;

class AdminPersonFilesAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(Person $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_DELETE => 'deleteFile',
                Request::METHOD_GET    => 'getFiles'  ,
            ];

            $payload   = json_decode($this->request->getContent(), true);
            $operation = $methods[$request->getMethod()];
            $result    = $this->$operation($data, $payload);

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);

        } catch (\Exception $e) {

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);

        }
    }

    private function deleteFile(Person $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('File id is not defined');
            }

            $company    = $this->manager->getRepository(People::class)->find($person->getId());
            $particular = $this->manager->getRepository(Particulars::class)->find($payload['id']);
            if ($particular === null) {
                throw new \InvalidArgumentException('File was not found');
            }

            if ($particular->getPeople() !== $company) {
                throw new \InvalidArgumentException('Access denied');
            }

            $fileValue  = empty($particular->getValue()) ? null : @json_decode($particular->getValue());
            if (empty($fileValue) || !is_object($fileValue)) {
                throw new \InvalidArgumentException('File data is corrupted');
            }

            // delete file

            $storedFile = $this->manager->getRepository(File::class)->find($fileValue->file);
            if ($storedFile === null) {
                throw new \InvalidArgumentException('Stored file was not found');
            }

            $this->manager->remove($storedFile);

            // delete particular

            $this->manager->remove($particular);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getFiles(Person $person, ?array $payload = null): array
    {
        $particulars = [];

        $company = $this->manager->getRepository(People::class)->find($person->getId());
        $files   = $this->manager->getRepository(Particulars::class)->getParticularsByPeopleAndFieldType($company, ['file']);

        if (!empty($files)) {
            foreach ($files as $particular) {
                $particulars[] = [
                    'id'    => $particular['id'],
                    'type'  => [
                        'id'    => $particular['type_id'],
                        'value' => $particular['type_value'],
                    ],
                    'value' => empty($particular['value']) ? null : @json_decode($particular['value'])
                ];
            }
        }

        return $particulars;
    }
}
