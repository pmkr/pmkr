<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputConverter;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\OutputConverter\InstanceConverter;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Pmkr\Pmkr\OutputConverter\InstanceConverter
 */
class InstanceConverterTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesToFlatRows(): array
    {
        return [
            'empty' => [
                [],
                [],
                true,
            ],
            'basic human-1' => [
                [
                    'i1' => [
                        'key' => '<fg=red>i1</>',
                        'coreVersion' => '7.4.0',
                        'isZts' => '<fg=red>⨯</>',
                        'installed' => '<fg=red>⨯</>',
                        'coreNameSuffix' => null,
                        'coreName' => '0704-nts',
                        'extensionSetNameSuffix' => '',
                        'extensionSetName' => '0704-nts',
                    ],
                    'i2' => [
                        'key' => '<fg=red>i2</>',
                        'coreVersion' => '7.4.0',
                        'isZts' => '<fg=red>⨯</>',
                        'installed' => '<fg=red>⨯</>',
                        'coreNameSuffix' => null,
                        'coreName' => '0704-nts',
                        'extensionSetNameSuffix' => '',
                        'extensionSetName' => '0704-nts',
                    ],
                ],
                [
                    'extensions' => [],
                    'extensionSets' => [
                        '0704-nts' => [],
                    ],
                    'cores' => [
                        '0704-nts' => [],
                    ],
                    'instances' => [
                        'i1' => [
                            'key' => 'i1',
                            'coreVersion' => '7.4.0',
                        ],
                        'i2' => [
                            'key' => 'i2',
                            'coreVersion' => '7.4.0',
                        ],
                    ],
                ],
                true,
            ],
            'basic human-0' => [
                [
                    'i1' => [
                        'key' => 'i1',
                        'coreVersion' => '7.4.0',
                        'isZts' => false,
                        'installed' => false,
                        'coreNameSuffix' => null,
                        'coreName' => '0704-nts',
                        'extensionSetNameSuffix' => '',
                        'extensionSetName' => '0704-nts',
                    ],
                    'i2' => [
                        'key' => 'i2',
                        'coreVersion' => '7.4.0',
                        'isZts' => false,
                        'installed' => false,
                        'coreNameSuffix' => null,
                        'coreName' => '0704-nts',
                        'extensionSetNameSuffix' => '',
                        'extensionSetName' => '0704-nts',
                    ],
                ],
                [
                    'extensions' => [],
                    'extensionSets' => [
                        '0704-nts' => [],
                    ],
                    'cores' => [
                        '0704-nts' => [],
                    ],
                    'instances' => [
                        'i1' => [
                            'key' => 'i1',
                            'coreVersion' => '7.4.0',
                        ],
                        'i2' => [
                            'key' => 'i2',
                            'coreVersion' => '7.4.0',
                        ],
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $configLayer
     */
    #[DataProvider('casesToFlatRows')]
    public function testToFlatRows(array $expected, array $configLayer, bool $isHuman): void
    {
        $config = $this->tester->grabConfig(null, $configLayer);
        $pmkr = PmkrConfig::__set_state([
            'config' => $config,
            'configPath' => [],
        ]);
        $utils = new Utils($config);
        $fs = new Filesystem();

        $converter = new InstanceConverter($utils, $fs);
        $this->tester->assertSame(
            $expected,
            $converter->toFlatRows($pmkr->instances, $isHuman),
        );
    }
}
