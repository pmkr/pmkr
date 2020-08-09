<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read null|string $key
 * @property-read bool $enabled
 * @property-read null|string $versionConstraint
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
        'versionConstraint' => [],
        'issue' => [],
        'description' => [],
        'uri' => [],
        'checksum' => [
            'type' => Checksum::class,
        ],
    ];
}
