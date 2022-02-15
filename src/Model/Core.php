<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read array<string, array{
 *     packages: array<string, bool>,
 * }> $dependencies
 * @property-read bool[] $patchList
 * @property-read array<string, array<string, null|string>> $configureEnvVar
 * @property-read array<string, array<string, null|false|string>> $configure
 * @property-read array $etc
 */
class Core extends Base
{
    /**
     * {@inheritdoc}
     */
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
