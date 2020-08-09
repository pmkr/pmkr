<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $hashChecksum
 * @property-read string $hashAlgorithm
 * @property-read int $hashFlags
 * @property-read string $hashKey
 */
class Checksum extends Base
{
    protected array $propertyMapping = [
        'hashChecksum' => [],
        'hashAlgorithm' => [],
        'hashFlags' => [],
        'hashKey' => [],
    ];
}
