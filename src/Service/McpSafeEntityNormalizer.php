<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class McpSafeEntityNormalizer
{
    public function __construct(private readonly NormalizerInterface $normalizer)
    {
    }

    /** Normalize an already-authorized entity with its domain read group and scrub nested secrets. */
    public function normalize(object $entity, string $group): array
    {
        $data = $this->normalizer->normalize($entity, 'json', [
            'groups' => [$group],
            'enable_max_depth' => true,
            'circular_reference_handler' => static fn (object $object): mixed => method_exists($object, 'getId') ? $object->getId() : null,
        ]);
        return $this->sanitize(is_array($data) ? $data : []);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function sanitize(array $data): array
    {
        $safe = [];
        foreach ($data as $key => $value) {
            if (preg_match('/password|passwd|api.?key|secret|credential|token|chargecapability|private.?key|access.?key/', strtolower((string) $key)) === 1) {
                continue;
            }
            if (is_array($value)) {
                $value = array_is_list($value)
                    ? array_map(fn (mixed $item): mixed => is_array($item) ? $this->sanitize($item) : $item, $value)
                    : $this->sanitize($value);
            }
            $safe[$key] = $value;
        }
        return $safe;
    }
}
