<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read null|string $key
 * @property-read bool $enabled
 * @property-read array{
 *     opSys?: array<string, bool>,
 *     versionConstraint?: string,
 * } $when
 * @property-read int|float $weight
 * @property-read null|string $issue
 * @property-read null|string $description
 * @property-read null|string $uri
 * @property-read \Pmkr\Pmkr\Model\Checksum $checksum
 */
class Patch extends Base
{
    protected array $propertyMapping = [
        'key' => [],
        'enabled' => [],
        'weight' => [],
        'when' => [
            'default' => [],
        ],
        'issue' => [],
        'description' => [],
        'uri' => [],
        'checksum' => [
            'type' => Checksum::class,
        ],
    ];
}
