<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read array $dependencies
 * @property-read bool[] $patchList
 * @property-read false[]|string[] $configureEnvVar
 * @property-read false[]|null[]|string[] $configure
 * @property-read array $etc
 */
class Core extends Base
{
    protected array $propertyMapping = [
        'dependencies' => [
            'default' => [],
        ],
        'patchList' => [
            'default' => [],
        ],
        'configureEnvVar' => [
            'default' => [],
        ],
        'configure' => [
            'default' => [],
        ],
        'etc' => [
            'default' => [],
        ],
    ];
}
