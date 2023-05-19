<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\VariationPickResult;

use Codeception\Attribute\DataProvider;
use Pmkr\Pmkr\VariationPickResult\VariationPickResult;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\VariationPickResult\VariationPickResult
 */
class VariationPickResultTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesAllInOne(): array
    {
        return [
            'empty' => [
                null,
                [],
            ],
            'all in one' => [
                '/a' . \DIRECTORY_SEPARATOR .'/b',
                [
                    'weight' => 42,
                    'phpRc' => '/my/php.ini',
                    'phpIniScanDir' => ['/a', '/b'],
                    'binary' => 'php-config',
                    'export' => true,
                ],
            ],
        ];
    }

    /**
     * @param ?string $expected
     * @param array{
     *     weight?: int|float,
     *     instance?: ?\Pmkr\Pmkr\Model\Instance,
     *     phpRc?: ?string,
     *     phpIniScanDir?: ?array<string>,
     *     binary?: ?string,
     * } $values
     */
    #[DataProvider('casesAllInOne')]
    public function testAllInOne(
        ?string $expected,
        array $values
    ): void {
        $result = VariationPickResult::__set_state($values);
        $this->tester->assertSame($expected, $result->implodePhpIniScanDir());
    }
}
