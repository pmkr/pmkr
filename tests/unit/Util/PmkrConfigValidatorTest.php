<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

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
        ];
    }

    /**
     * @dataProvider casesValidate
     */
    public function testValidate($expected, array $configLayer): void
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
