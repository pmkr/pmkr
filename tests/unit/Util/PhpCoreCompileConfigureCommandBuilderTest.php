<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\ConfigNormalizer;
use Pmkr\Pmkr\Util\PhpCoreCompileConfigureCommandBuilder;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\Util\PhpCoreCompileConfigureCommandBuilder<extended>
 */
class PhpCoreCompileConfigureCommandBuilderTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesBuild(): array
    {
        return [
            'nts' => [
                implode("\n", [
                    "PKG_CONFIG_PATH='/path/to/OpenSSL_1_1_1t/lib/pkgconfig' \\",
                    "CORE_AK='core_av' \\",
                    "EXT01_AK='ext01_av' \\",
                    './configure \\',
                    "    --prefix='/home/me/slash/usr/share/pmkr-php-inst01' \\",
                    "    --with-config-file-path='/home/me/slash/usr/share/pmkr-php-inst01/etc' \\",
                    "    --with-config-file-scan-dir='/home/me/slash/usr/share/pmkr-php-inst01/etc/conf/default' \\",
                    '    --disable-all \\',
                    '    --core-conf-01 \\',
                    "    --core-conf-03='val-03' \\",
                    '    --enable-ext01'
                ]),
                null,
                [
                    'libraries' => [
                        'OpenSSL_1_1' => [
                            'name' => 'OpenSSL_1_1_1t',
                            'parentConfigureEnvVars' => [
                                'PKG_CONFIG_PATH' => [
                                    'default' => [
                                        'pkgconfig' => [
                                            'enabled' => true,
                                            'weight' => 1,
                                            'value' => '/path/to/OpenSSL_1_1_1t/lib/pkgconfig',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'extensions' => [
                        'ext01' => [
                            'dependencies' => [
                                'libraries' => [
                                    'default' => [
                                        'OpenSSL_1_1' => true,
                                    ],
                                ],
                            ],
                            'configureEnvVar' => [
                                'default' => [
                                    'EXT01_AK' => 'ext01_av',
                                ],
                            ],
                            'configure' => [
                                'default' => [
                                    '--enable-ext01' => null,
                                ],
                            ],
                        ],
                        'ext02' => [
                            'configure' => [
                                'default' => [
                                    '--enable-ext01' => 'foo',
                                ],
                            ],
                        ],
                    ],
                    'extensionSets' => [
                        '080100-esns01' => [
                            'ext01' => [
                                'status' => 'enabled',
                            ],
                            'ext02' => [
                                'status' => 'optional',
                            ],
                        ],
                    ],
                    'cores' => [
                        '080100-cns01' => [
                            'configureEnvVar' => [
                                'default' => [
                                    'CORE_AK' => 'core_av',
                                ],
                            ],
                            'configure' => [
                                'default' => [
                                    '--core-conf-01' => null,
                                    '--core-conf-02' => false,
                                    '--core-conf-03' => 'val-03',
                                ],
                            ],
                        ],
                    ],
                    'instances' => [
                        'inst01' => [
                            'coreVersion' => '8.1.0',
                            'isZts' => false,
                            'coreNameSuffix' => 'cns01',
                            'extensionSetNameSuffix' => 'esns01',
                        ],
                    ],
                ],
            ],
            'zts' => [
                implode("\n", [
                    './configure \\',
                    "    --prefix='/home/me/slash/usr/share/pmkr-php-inst01' \\",
                    "    --with-config-file-path='/home/me/slash/usr/share/pmkr-php-inst01/etc' \\",
                    "    --with-config-file-scan-dir='/home/me/slash/usr/share/pmkr-php-inst01/etc/conf/default' \\",
                    '    --disable-all \\',
                    '    --core-conf-01 \\',
                    "    --core-conf-03='val-03' \\",
                    '    --enable-ext01'
                ]),
                null,
                [
                    'extensions' => [
                        'ext01' => [
                            'configure' => [
                                'default' => [
                                    '--enable-ext01' => null,
                                ],
                            ],
                        ],
                    ],
                    'extensionSets' => [
                        '080100-esns01' => [
                            'ext01' => [
                                'status' => 'enabled',
                            ],
                        ],
                    ],
                    'cores' => [
                        '080100-cns01' => [
                            'configure' => [
                                'default' => [
                                    '--core-conf-01' => null,
                                    '--core-conf-02' => false,
                                    '--core-conf-03' => 'val-03',
                                ],
                            ],
                        ],
                    ],
                    'instances' => [
                        'inst01' => [
                            'coreVersion' => '8.1.0',
                            'isZts' => true,
                            'coreNameSuffix' => 'cns01',
                            'extensionSetNameSuffix' => 'esns01',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param null|array<string, mixed> $configDefaultLayer
     * @param array<string, mixed> $configLayerApp
     *
     * @dataProvider casesBuild
     */
    public function testBuild(string $expected, ?array $configDefaultLayer, array $configLayerApp): void
    {
        $config = $this->tester->grabConfig($configDefaultLayer, $configLayerApp);
        $configNormalizer = new ConfigNormalizer();
        $configNormalizer->normalizeConfig($config);

        $instance = Instance::__set_state([
            'config' => $config,
            'configPath' => ['instances', 'inst01'],
        ]);

        $utils = new Utils($config);
        $opSys = $this->tester->grabOpSys('opensuse-tumbleweed');
        $builder = new PhpCoreCompileConfigureCommandBuilder($utils, $opSys);
        $this->tester->assertSame($expected, $builder->build($instance));
    }
}
