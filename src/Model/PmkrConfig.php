<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $version
 * @property-read array $app
 * @property-read \Pmkr\Pmkr\Model\Directories $dir
 * @property-read \Pmkr\Pmkr\Model\Patches|\Pmkr\Pmkr\Model\Patch[] $patches
 * @property-read \Pmkr\Pmkr\Model\Libraries|\Pmkr\Pmkr\Model\Library[] $libraries
 * @property-read \Pmkr\Pmkr\Model\Cores|\Pmkr\Pmkr\Model\Core[] $cores
 * @property-read \Pmkr\Pmkr\Model\Extensions $extensions
 * @property-read \Pmkr\Pmkr\Model\ExtensionSets $extensionSets
 * @property-read \Pmkr\Pmkr\Model\Instances|\Pmkr\Pmkr\Model\Instance[] $instances
 * @property-read string[] $aliases
 * @property-read \Pmkr\Pmkr\Model\Variations|\Pmkr\Pmkr\Model\Variation[] $variations
 * @property-read ?string $defaultVariationKey
 * @property-read null|\Pmkr\Pmkr\Model\Variation defaultVariation
 */
class PmkrConfig extends Base
{
    protected array $propertyMapping = [
        'version' => [],
        'app' => [],
        'dir' => [
            'type' => Directories::class,
        ],
        'patches' => [
            'type' => Patches::class,
        ],
        'libraries' => [
            'type' => Libraries::class,
        ],
        'cores' => [
            'type' => Cores::class,
        ],
        'extensions' => [
            'type' => Extensions::class,
        ],
        'extensionSets' => [
            'type' => ExtensionSets::class,
        ],
        'instances' => [
            'type' => Instances::class,
        ],
        'aliases' => [
            'default' => [],
        ],
        'variations' => [
            'type' => Variations::class,
        ],
        'defaultVariationKey' => [
            'default' => null,
        ],
        'defaultVariation' => [
            'type' => 'callback',
            'callback' => 'defaultVariation',
        ],
    ];

    protected function defaultVariation(): ?Variation
    {
        $defVarKey = $this->defaultVariationKey;
        if (!$defVarKey) {
            return null;
        }

        $config = $this->getConfig();
        if (!$config->has("variations.$defVarKey")) {
            return null;
        }

        return Variation::__set_state([
            'config' => $config,
            'configPath' => ['variations', $defVarKey],
        ]);
    }
}
