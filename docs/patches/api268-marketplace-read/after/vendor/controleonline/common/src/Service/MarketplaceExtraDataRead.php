<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\ExtraFields;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/** Read-only endpoint snapshot, bounded to entities actually inspected by the response. */
final class MarketplaceExtraDataRead
{
    private const ATTRIBUTE = '_controleonline_marketplace_extra_data';

    public static function supports(?Request $request): bool
    {
        return $request?->isMethod('GET') && in_array($request->getPathInfo(), [
            '/marketplace/integrations', '/marketplace/integrations/catalog/status',
        ], true);
    }

    public static function read(
        EntityManagerInterface $manager,
        Request $request,
        string $context,
        string $entityName,
        int $entityId,
        string $fieldName,
        string $fieldType
    ): ?string {
        $snapshot = $request->attributes->get(self::ATTRIBUTE, ['fields' => [], 'entities' => []]);
        $fieldKey = json_encode([$context, $fieldName, $fieldType], JSON_THROW_ON_ERROR);
        if (!array_key_exists($fieldKey, $snapshot['fields'])) {
            // Keep canonical field selection, including legacy duplicate definitions.
            $snapshot['fields'][$fieldKey] = $manager->getRepository(ExtraFields::class)
                ->findOneByContextNameType($context, $fieldName, $fieldType);
        }
        $field = $snapshot['fields'][$fieldKey];
        $entityKey = json_encode([$entityName, $entityId], JSON_THROW_ON_ERROR);
        if ($field instanceof ExtraFields && !array_key_exists($entityKey, $snapshot['entities'])) {
            $values = [];
            // Existing repository orders ASC: last row wins, matching scalar lookup DESC/LIMIT 1.
            foreach ($manager->getRepository(\ControleOnline\Entity\ExtraData::class)
                ->findByEntityNameEntityId($entityName, $entityId) as $row) {
                if ($row->getExtraFields() !== null) {
                    $values[$row->getExtraFields()->getId()] = trim((string) $row->getValue());
                }
            }
            $snapshot['entities'][$entityKey] = $values;
        }
        $request->attributes->set(self::ATTRIBUTE, $snapshot);
        $value = $field instanceof ExtraFields ? ($snapshot['entities'][$entityKey][$field->getId()] ?? '') : '';
        return $value !== '' ? $value : null;
    }
}
