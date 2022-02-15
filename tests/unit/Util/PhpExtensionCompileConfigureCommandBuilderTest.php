<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\ConfigNormalizer;
use Pmkr\Pmkr\Util\PhpExtensionCompileConfigureCommandBuilder;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\Util\PhpExtensionCompileConfigureCommandBuilder
 */
class PhpExtensionCompileConfigureCommandBuilderTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesBuild(): array
    {
        return [
            'basic' => [
                implode("\n", [
                    "MY_VAR_A='suse' \\",
                    './configure \\',
                    "    --with-php-config='/home/me/bin/php-config' \\",
                    '    --enable-ext01 \\',
                    "    --ext01-opensuse='tumbleweed'",
                ]),
                null,
                [
                    'extensions' => [
                        'ext01' => [
                            'configureEnvVar' => [
                                'default' => [
                                    'MY_VAR_A' => false,
                                ],
                                'opensuse-tumbleweed' => [
                                    'MY_VAR_A' => 'suse',
                                ],
                                'ubuntu' => [
                                    'MY_VAR_A' => 'ubuntu',
                                ],
                            ],
                            'configure' => [
                                'default' => [
                                    '--enable-ext01' => null,
                                ],
                                'opensuse-tumbleweed' => [
                                    '--ext01-opensuse' => 'tumbleweed',
                                ],
                                'ubuntu' => [
                                    '--ext01-ubuntu' => 'foo',
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
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configDefaultLayer
     * @param array<string, mixed> $configLayerApp
     *
     * @dataProvider casesBuild
     */
    public function testBuild(string $expected, ?array $configDefaultLayer, array $configLayerApp): void
    {
        $config = $this->tester->grabConfig($configDefaultLayer, $configLayerApp);
        $configNormalizer = new ConfigNormalizer();
        $configNormalizer->normalizeConfig($config);

        $extension = Extension::__set_state([
            'config' => $config,
            'configPath' => ['extensions', 'ext01'],
        ]);
        $extensionSrcDir = '/src/ext01';
        $phpBinDir = '/home/me/bin';

        $utils = new Utils($config);
        $opSys = $this->tester->grabOpSys('opensuse-tumbleweed');
        $builder = new PhpExtensionCompileConfigureCommandBuilder($utils, $opSys);
        $this->tester->assertSame($expected, $builder->build($extension, $extensionSrcDir, $phpBinDir));
    }
}
