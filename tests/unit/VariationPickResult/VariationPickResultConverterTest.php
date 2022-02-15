<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\VariationPickResult;

use Codeception\Test\Unit;
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
     *
     * @dataProvider casesToShellVarSetter
     */
    public function testToShellVarSetter(
        string $expected,
        array $resultState,
        array $configLayer = [],
        array $envVars = []
    ): void {
        $config = $this->tester->grabConfig(null, $configLayer);
        $instance = null;
        if (isset($configLayer['instances']['my01'])) {
            $instance = Instance::__set_state([
                'config' => $config,
                'configPath' => ['instances', 'my01'],
            ]);
        }
        $resultState['instance'] = $instance;
        $result = VariationPickResult::__set_state($resultState);

        $envPathHandler = new EnvPathHandler($config);
        $envVarStorage = new EnvVarStorage(new \ArrayObject($envVars));
        $converter = new VariationPickResultConverter($envPathHandler, $envVarStorage);

        $this->tester->assertSame(
            $expected,
            $converter->toShellVarSetter($result),
        );
    }
}
