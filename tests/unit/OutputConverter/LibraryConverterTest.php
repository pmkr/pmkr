<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputConverter;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\OutputConverter\LibraryConverter;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\OutputConverter\LibraryConverter
 */
class LibraryConverterTest extends Unit
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
                    'a-key' => [
                        'key' => 'a-key',
                        'name' => 'a-name',
                    ],
                    'b-key' => [
                        'key' => 'b-key',
                        'name' => 'b-name',
                    ],
                ],
                [
                    'libraries' => [
                        'a-key' => [
                            'key' => 'a-key',
                            'name' => 'a-name',
                        ],
                        'b-key' => [
                            'key' => 'b-key',
                            'name' => 'b-name',
                        ],
                    ],
                ],
                true,
            ],
            'basic human-0' => [
                [
                    'a-key' => [
                        'key' => 'a-key',
                        'name' => 'a-name',
                    ],
                    'b-key' => [
                        'key' => 'b-key',
                        'name' => 'b-name',
                    ],
                ],
                [
                    'libraries' => [
                        'a-key' => [
                            'key' => 'a-key',
                            'name' => 'a-name',
                        ],
                        'b-key' => [
                            'key' => 'b-key',
                            'name' => 'b-name',
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
        $pmkr = PmkrConfig::__set_state([
            'config' => $this->tester->grabConfig(null, $configLayer),
            'configPath' => [],
        ]);

        $converter = new LibraryConverter();
        $this->tester->assertSame(
            $expected,
            $converter->toFlatRows($pmkr->libraries, $isHuman),
        );
    }
}
