<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read array $dependencies
 * @property-read bool[] $patchList
 * @property-read \Pmkr\Pmkr\Model\Patch[] $patches
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
        'patches' => [
            'type' => 'callback',
            'callback' => 'patches',
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

    protected function patches(): array
    {
        $configPathRoot = array_slice($this->getConfigPath(), 0, -2);
        $configPathPatches = array_merge($configPathRoot, ['patches']);
        $patches = $this->getConfig()->get(implode('.', $configPathPatches));
        $result = [];
        foreach (array_keys($this->patchList) as $key) {
            $result[$key] = $patches[$key];
        }

        return $result;
    }
}
