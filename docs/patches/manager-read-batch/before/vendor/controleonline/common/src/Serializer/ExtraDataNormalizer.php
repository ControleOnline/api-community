<?php

namespace ControleOnline\Serializer;

use ControleOnline\Service\ExtraDataService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ExtraDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(private ExtraDataService $extraDataService) {}

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (is_array($data)) {
            return !isset($context['_extra_data_cache']);
        }

        return is_object($data)
            && method_exists($data, 'setExtraData')
            && empty($context['_extra_data_applied'][spl_object_id($data)]);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // Share reads across branches of this serialization only, never requests.
        $context['_extra_data_cache'] ??= new \WeakMap();
        $context['exclude_from_cache_key'] = array_unique(array_merge(
            $context['exclude_from_cache_key'] ?? [], ['_extra_data_cache', '_extra_data_applied'],
        ));
        if (is_array($object)) {
            return $this->normalizer->normalize($object, $format, $context);
        }
        $cache = $context['_extra_data_cache'];
        $objectId = spl_object_id($object);
        $context['_extra_data_applied'][$objectId] = true;

        if (isset($cache[$object])) {
            $object->setExtraData($cache[$object]);
            return $this->normalizer->normalize($object, $format, $context);
        }

        $extraDataEntities = $this->extraDataService->getExtraDataFromEntity($object);

        $extraDataArray = [];
        foreach ($extraDataEntities as $extraData) {
            $extraDataArray[] = [
                'id' => $extraData->getId(),
                'entity_id' => $extraData->getEntityId(),
                'entity_name' => $extraData->getEntityName(),
                'value' => $extraData->getValue(),
                'source' => $extraData->getSource(),
                'extra_fields' => [
                    'id' => $extraData->getExtraFields()->getId(),
                    'name' => $extraData->getExtraFields()->getName(),
                    'type' => $extraData->getExtraFields()->getType(),
                    'context' => $extraData->getExtraFields()->getContext(),
                    'configs' => $extraData->getExtraFields()->getConfigs(),
                    'required' => $extraData->getExtraFields()->getRequired()
                ]
            ];
        }

        $cache[$object] = $extraDataArray;
        $object->setExtraData($extraDataArray);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
            'object' => false,
        ];
    }
}
