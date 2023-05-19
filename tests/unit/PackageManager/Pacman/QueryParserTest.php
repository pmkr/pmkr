<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager\Pacman;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Pacman\QueryParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Pacman\QueryParser
 */
class QueryParserTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesParseMissing(): array
    {
        return [
            'empty' => [
                [
                    'missing' => [],
                    'installed' => [],
                    'not-installed' => [],
                ],
                [],
                0,
                '',
                '',
            ],
            'basic' => [
                [
                    'missing' => [],
                    'installed' => [
                        'curl' => [
                            'Name' => 'curl',
                            'Version' => '7.81.0-1',
                        ],
                        'php' => [
                            'Name' => 'php',
                            'Version' => '8.0.14-1',
                            'Depends On' => 'ca-certificates',
                        ],
                    ],
                    'not-installed' => [
                        'vim' => [],
                        'other' => [],
                    ],
                ],
                [
                    'curl',
                    'php',
                    'vim',
                    'other',
                ],
                0,
                implode("\n", [
                    'Name            : curl',
                    'Version         : 7.81.0-1',
                    '',
                    'Name            : php',
                    'Version         : 8.0.14-1',
                    'Depends On      : ca-certificates',
                    '                  dependency-02',
                    '',
                ]),
                implode("\n", [
                    "error: package 'vim' was not found",
                    "error: package 'other' was not found",
                ]),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string> $packageNames
     */
    #[DataProvider('casesParseMissing')]
    public function testParseMissing(
        array $expected,
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): void {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);
        $parser = new QueryParser($utils);
        $this->tester->assertSame(
            $expected,
            $parser->parseMissing($packageNames, $exitCode, $stdOutput, $stdError),
        );
    }
}
