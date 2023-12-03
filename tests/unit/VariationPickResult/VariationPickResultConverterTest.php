<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\VariationPickResult;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\VariationPickResult\VariationPickResult;
use Pmkr\Pmkr\VariationPickResult\VariationPickResultConverter;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\EnvPathHandler;
use Sweetchuck\EnvVarStorage\ArrayStorage as EnvVarStorage;

/**
 * @covers \Pmkr\Pmkr\VariationPickResult\VariationPickResultConverter
 */
class VariationPickResultConverterTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesToShellVarSetter(): array
    {
        $my01Path = implode(':', [
            '/home/me/slash/usr/share/pmkr-php-my01/bin',
            '/home/me/slash/usr/share/pmkr-php-my01/sbin',
        ]);
        $oldPath = implode(':', [
            '/home/me/slash/usr/share/pmkr-php-old/bin',
            '/home/me/slash/usr/share/pmkr-php-old/sbin',
        ]);

        return [
            'empty' => [
                implode("\n", [
                    "export PATH='' ;",
                    'unset PHPRC ;',
                    'unset PHP_INI_SCAN_DIR ;',
                    '',
                ]),
                [],
            ],
            'new' => [
                implode("\n", [
                    "export PATH='$my01Path:/a:/b:/c/d' ;",
                    'unset PHPRC ;',
                    'unset PHP_INI_SCAN_DIR ;',
                    '',
                ]),
                [],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [
                    'PATH' => '/a:/b:/c/d',
                ],
            ],
            'replace' => [
                implode("\n", [
                    "export PATH='/a:$my01Path:/b:/c/d' ;",
                    'unset PHPRC ;',
                    'unset PHP_INI_SCAN_DIR ;',
                    '',
                ]),
                [],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [
                    'PATH' => "/a:$oldPath:/b:/c/d",
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $resultState
     * @param array<string, mixed> $configLayer
     * @param array<string, string> $envVars
     */
    #[DataProvider('casesToShellVarSetter')]
    public function testToShellVarSetter(
        string $expected,
        array $resultState,
        array $configLayer = [],
        array $envVars = [],
    ): void {
        $config = $this->tester->grabConfig(null, $configLayer);
        $result = $this->createPickResult($config, $resultState);
        $converter = $this->createPickResultConverter($config, $envVars);

        $this->tester->assertSame(
            $expected,
            $converter->toShellVarSetter($result),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesToProcessArgs(): array
    {
        return [
            'empty' => [
                null,
                [],
                [],
                [],
            ],
            'basic' => [
                [
                    'command' => [
                        '/home/me/slash/usr/share/pmkr-php-my01/bin/php',
                    ],
                    'envVars' => [],
                ],
                [],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [],
            ],
            'with env vars' => [
                [
                    'command' => [
                        '/home/me/slash/usr/share/pmkr-php-my01/bin/php',
                    ],
                    'envVars' => [
                        'PHPRC' => '/my/php.ini',
                    ],
                ],
                [
                    'phpRc' => '/my/php.ini',
                ],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @param ?array<string, mixed> $expected
     * @param array<string, mixed> $resultState
     * @param array<string, mixed> $configLayer
     * @param array<string, string> $envVars
     */
    #[DataProvider('casesToProcessArgs')]
    public function testToProcessArgs(
        ?array $expected,
        array $resultState,
        array $configLayer = [],
        array $envVars = [],
    ): void {
        $config = $this->tester->grabConfig(null, $configLayer);
        $result = $this->createPickResult($config, $resultState);
        $converter = $this->createPickResultConverter($config, $envVars);

        $this->tester->assertSame(
            $expected,
            $converter->toProcessArgs($result),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesToShellExecutable(): array
    {
        return [
            'empty' => [
                null,
                [],
                [],
                [],
            ],
            'basic' => [
                '/home/me/slash/usr/share/pmkr-php-my01/bin/php',
                [],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [],
            ],
            'with env vars' => [
                "PHPRC='/my/php.ini' /home/me/slash/usr/share/pmkr-php-my01/bin/php",
                [
                    'phpRc' => '/my/php.ini',
                ],
                [
                    'instances' => [
                        'my01' => [
                            'key' => 'my01',
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $resultState
     * @param array<string, mixed> $configLayer
     * @param array<string, string> $envVars
     */
    #[DataProvider('casesToShellExecutable')]
    public function testToShellExecutable(
        ?string $expected,
        array $resultState,
        array $configLayer = [],
        array $envVars = [],
    ): void {
        $config = $this->tester->grabConfig(null, $configLayer);
        $result = $this->createPickResult($config, $resultState);
        $converter = $this->createPickResultConverter($config, $envVars);

        $this->tester->assertSame(
            $expected,
            $converter->toShellExecutable($result),
        );
    }

    /**
     * @param array<string, string> $envVars
     */
    protected function createPickResultConverter(
        ConfigInterface $config,
        array $envVars = [],
    ): VariationPickResultConverter {
        $envPathHandler = new EnvPathHandler($config);
        $envVarStorage = new EnvVarStorage(new \ArrayObject($envVars));

        return new VariationPickResultConverter($envPathHandler, $envVarStorage);
    }

    /**
     * @param array<string, mixed> $resultState
     */
    protected function createPickResult(
        ConfigInterface $config,
        array $resultState,
    ): VariationPickResult {
        $instance = null;
        if ($config->has('instances.my01')) {
            $instance = Instance::__set_state([
                'config' => $config,
                'configPath' => ['instances', 'my01'],
            ]);
        }
        $resultState['instance'] = $instance;

        return VariationPickResult::__set_state($resultState);
    }
}
