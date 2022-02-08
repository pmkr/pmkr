<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util\Filter;

use Codeception\Test\Unit;
use Composer\Semver\VersionParser;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\Filter\PatchFilter;

/**
 * @covers \Pmkr\Pmkr\Util\Filter\PatchFilter
 */
class PatchFilterTest extends Unit
{

    protected UnitTester $tester;

    public function casesCheck(): array
    {
        return [
            'when empty' => [
                true,
                [],
                [
                    'patches' => [
                        'my-patch-01' => [],
                    ],
                ],
            ],
            'when.versionConstraint lower' => [
                false,
                [
                    'version' => '8.1.1',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'versionConstraint' => '>=8.1.2 <8.1.5',
                            ],
                        ],
                    ],
                ],
            ],
            'when.versionConstraint match' => [
                true,
                [
                    'version' => '8.1.3',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'versionConstraint' => '>=8.1.2 <8.1.5',
                            ],
                        ],
                    ],
                ],
            ],
            'when.versionConstraint higher' => [
                false,
                [
                    'version' => '8.1.6',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'versionConstraint' => '>=8.1.2 <8.1.5',
                            ],
                        ],
                    ],
                ],
            ],
            'when.opSys fallback to default: true' => [
                true,
                [
                    'opSys' => 'ubuntu-21-10',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'opSys' => [
                                    'default' => true,
                                    'opensuse-tumbleweed' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'when.opSys fallback to default: false' => [
                false,
                [
                    'opSys' => 'ubuntu-21-10',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'opSys' => [
                                    'default' => false,
                                    'opensuse-tumbleweed' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'when.opSys match true' => [
                true,
                [
                    'opSys' => 'opensuse-tumbleweed',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'opSys' => [
                                    'default' => false,
                                    'opensuse-tumbleweed' => true,
                                    'ubuntu-21-10' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'when.opSys match false' => [
                false,
                [
                    'opSys' => 'opensuse-tumbleweed',
                ],
                [
                    'patches' => [
                        'my-patch-01' => [
                            'when' => [
                                'opSys' => [
                                    'default' => true,
                                    'opensuse-tumbleweed' => false,
                                    'ubuntu-21-10' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // @todo Combine versionConstraint and opSys.
        ];
    }

    /**
     * @dataProvider casesCheck
     */
    public function testCheck(bool $expected, array $options, array $configLayer): void
    {
        if (isset($options['opSys'])) {
            $options['opSys'] = $this->tester->grabOpSys($options['opSys']);
        }

        $versionParser = new VersionParser();
        $filter = new PatchFilter($versionParser);
        $filter->setOptions($options);

        $config = $this->tester->grabConfig(null, $configLayer);
        $pmkr = PmkrConfig::__set_state([
            'config' => $config,
            'configPath' => [],
        ]);

        $patch = $pmkr->patches['my-patch-01'];
        $this->tester->assertSame($expected, $filter->check($patch));
    }
}
