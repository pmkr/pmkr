<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $version
 * @property-read array{name: string, label: string, version: string} $app
 * @property-read \Pmkr\Pmkr\Model\Directories $dir
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Patch> $patches
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Library> $libraries
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Core> $cores
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Extension> $extensions
 * @property-read \Pmkr\Pmkr\Model\Collection<
 *     \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\ExtensionSetItem>
 * > $extensionSets
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Instance> $instances
 * @property-read array<string, string> $aliases
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Variation> $variations
 * @property-read ?string $defaultVariationKey
 * @property-read null|\Pmkr\Pmkr\Model\Variation $defaultVariation
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
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Patch::class,
                    ],
                ],
            ],
        ],
        'libraries' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Library::class,
                    ],
                ],
            ],
        ],
        'cores' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Core::class,
                    ],
                ],
            ],
        ],
        'extensions' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Extension::class,
                    ],
                ],
            ],
        ],
        'extensionSets' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Collection::class,
                        'state' => [
                            'propertyMapping' => [
                                '' => [
                                    'type' => ExtensionSetItem::class,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'instances' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Instance::class,
                    ],
                ],
            ],
        ],
        'aliases' => [
            'default' => [],
        ],
        'variations' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Variation::class,
                    ],
                ],
            ],
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
