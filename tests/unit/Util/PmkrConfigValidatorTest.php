<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\PmkrConfigValidator;

/**
 * @covers \Pmkr\Pmkr\Util\PmkrConfigValidator
 */
class PmkrConfigValidatorTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesValidate(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],

            'core.patchList - one missing' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/cores/0801-nts/patchList/non-exists-01',
                    ],
                ],
                [
                    'patches' => [
                        'okay-01' => [],
                    ],
                    'cores' => [
                        '0801-nts' => [
                            'patchList' => [
                                'okay-01' => true,
                                'non-exists-01' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'core.patchList - two missing' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/cores/0801-nts/patchList/non-exists-01',
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/cores/0801-nts/patchList/non-exists-02',
                    ],
                ],
                [
                    'patches' => [
                        'okay-01' => [],
                    ],
                    'cores' => [
                        '0801-nts' => [
                            'patchList' => [
                                'non-exists-01' => true,
                                'okay-01' => true,
                                'non-exists-02' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'core.patchList - all exists' => [
                [],
                [
                    'patches' => [
                        'okay-01' => [],
                        'okay-02' => [],
                    ],
                    'cores' => [
                        '0801-nts' => [
                            'patchList' => [
                                'okay-01' => true,
                                'okay-02' => true,
                            ],
                        ],
                    ],
                ],
            ],

            'cores.dependencies.libraries - all exists' => [
                [],
                [
                    'libraries' => [
                        'lib-01' => [],
                        'lib-02' => [],
                        'lib-03' => [],
                    ],
                    'cores' => [
                        'core-01' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-01' => true,
                                    ],
                                ],
                            ],
                        ],
                        'core-02' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-02' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'cores.dependencies.libraries - two missing' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/cores/core-01/dependencies/libraries/opensuse-tumbleweed/lib-04',
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/cores/core-02/dependencies/libraries/opensuse-tumbleweed/lib-05',
                    ],
                ],
                [
                    'libraries' => [
                        'lib-01' => [],
                        'lib-02' => [],
                        'lib-03' => [],
                    ],
                    'cores' => [
                        'core-01' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-01' => true,
                                        'lib-04' => true,
                                    ],
                                ],
                            ],
                        ],
                        'core-02' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-02' => true,
                                        'lib-05' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'extensions.dependencies.libraries - all exists' => [
                [],
                [
                    'libraries' => [
                        'lib-01' => [],
                        'lib-02' => [],
                        'lib-03' => [],
                    ],
                    'extensions' => [
                        'ext-01' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-01' => true,
                                    ],
                                ],
                            ],
                        ],
                        'ext-02' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-02' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'extensions.dependencies.libraries - two missing' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/extensions/ext-01/dependencies/libraries/opensuse-tumbleweed/lib-04',
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/extensions/ext-02/dependencies/libraries/opensuse-tumbleweed/lib-05',
                    ],
                ],
                [
                    'libraries' => [
                        'lib-01' => [],
                        'lib-02' => [],
                        'lib-03' => [],
                    ],
                    'extensions' => [
                        'ext-01' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-01' => true,
                                        'lib-04' => true,
                                    ],
                                ],
                            ],
                        ],
                        'ext-02' => [
                            'dependencies' => [
                                'libraries' => [
                                    'opensuse-tumbleweed' => [
                                        'lib-02' => true,
                                        'lib-05' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'extensionSets - all exists' => [
                [],
                [
                    'extensions' => [
                        'ext-01' => [],
                        'ext-02' => [],
                        'ext-03' => [],
                    ],
                    'extensionSets' => [
                        'es-01' => [
                            'ext-01' => ['status' => 'enabled'],
                            'ext-02' => ['status' => 'enabled'],
                        ],
                    ],
                ],
            ],
            'extensionSets - one missing' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/extensionSets/es-01/ext-02',
                    ],
                ],
                [
                    'extensions' => [
                        'ext-01' => [],
                        'ext-03' => [],
                    ],
                    'extensionSets' => [
                        'es-01' => [
                            'ext-01' => ['status' => 'enabled'],
                            'ext-02' => ['status' => 'enabled'],
                        ],
                    ],
                ],
            ],

            'instance.coreName - exists' => [
                [],
                [
                    'cores' => [
                        'core-01' => [],
                        'core-02' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                        'bar' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'foo',
                        ],
                        'instance-02' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-02',
                            'extensionSetNameSuffix' => 'bar',
                        ],
                    ],
                ],
            ],
            'instance.coreName - not exists' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/instances/instance-01/coreNameSuffix'
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/instances/instance-02/coreNameSuffix'
                    ],
                ],
                [
                    'cores' => [
                        'core-01' => [],
                        'core-02' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                        'bar' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-03',
                            'extensionSetNameSuffix' => 'foo',
                        ],
                        'instance-02' => [
                            'coreVersion' => '4.5.6',
                            'coreNameSuffix' => 'core-04',
                            'extensionSetNameSuffix' => 'bar',
                        ],
                    ],
                ],
            ],
            'instance.extensionSetName - not exists' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/instances/instance-01/extensionSetNameSuffix'
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/instances/instance-02/extensionSetNameSuffix'
                    ],
                ],
                [
                    'cores' => [
                        'core-01' => [],
                        'core-02' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                        'bar' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'nope-1',
                        ],
                        'instance-02' => [
                            'coreVersion' => '4.5.6',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'nope-2',
                        ],
                    ],
                ],
            ],
            'aliases - not exists' => [
                [
                    [
                        'type' => 'alias_ambiguous',
                        'path' => '/aliases/instance-01',
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/aliases/alias-02',
                    ],
                    [
                        'type' => 'invalid_reference',
                        'path' => '/aliases/alias-03',
                    ],
                ],
                [
                    'cores' => [
                        'core-01' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'foo',
                        ],
                    ],
                    'aliases' => [
                        'alias-01' => 'instance-01',
                        'instance-01' => 'instance-01',
                        'alias-02' => 'instance-02',
                        'alias-03' => 'instance-03',
                    ],
                ],
            ],
            'defaultVariationKey - valid null' => [
                [],
                [
                    'defaultVariationKey' => null,
                ],
            ],
            'defaultVariationKey - valid exists' => [
                [],
                [
                    'cores' => [
                        'core-01' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'foo',
                        ],
                    ],
                    'variations' => [
                        'my-variation-01' => [
                            'key' => 'my-variation-01',
                            'instanceKey' => 'instance-01',
                        ],
                    ],
                    'defaultVariationKey' => 'my-variation-01',
                ],
            ],
            'defaultVariationKey - instance not exists' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/variations/my-variation-01/instanceKey',
                    ],
                ],
                [
                    'variations' => [
                        'my-variation-01' => [
                            'key' => 'my-variation-01',
                        ],
                    ],
                    'defaultVariationKey' => 'my-variation-01',
                ],
            ],
            'defaultVariationKey - variation not exists' => [
                [
                    [
                        'type' => 'invalid_reference',
                        'path' => '/defaultVariationKey',
                    ],
                ],
                [
                    'cores' => [
                        'core-01' => [],
                    ],
                    'extensionSets' => [
                        'foo' => [],
                    ],
                    'instances' => [
                        'instance-01' => [
                            'coreVersion' => '1.2.3',
                            'coreNameSuffix' => 'core-01',
                            'extensionSetNameSuffix' => 'foo',
                        ],
                    ],
                    'variations' => [
                        'my-variation-01' => [
                            'key' => 'my-variation-01',
                            'instanceKey' => 'instance-01',
                        ],
                    ],
                    'defaultVariationKey' => 'my-variation-02',
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{
     *     type: string,
     *     path: string,
     * }> $expected
     * @param array<string, mixed> $configLayer
     */
    #[DataProvider('casesValidate')]
    public function testValidate(array $expected, array $configLayer): void
    {
        $config = $this->tester->grabConfig(null, $configLayer);
        $pmkr = PmkrConfig::__set_state([
            'config' => $config,
            'configPath' => [],
        ]);

        $validator = new PmkrConfigValidator();
        $this->tester->assertSame(
            $expected,
            $validator->validate($pmkr),
        );
    }
}
