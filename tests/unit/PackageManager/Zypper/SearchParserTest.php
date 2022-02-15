<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager\Zypper;

use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Zypper\SearchParser;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Zypper\SearchParser
 */
class SearchParserTest extends Unit
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
                    'messages' => [],
                    'installed' => [],
                    'not-installed' => [],
                    'missing' => [],
                ],
                [],
                0,
                '',
                '',
            ],
            'basic' => [
                [
                    'messages' => [],
                    'installed' => [],
                    'not-installed' => [
                        'gvim' => [
                            'name' => 'gvim',
                            'status' => 'not-installed',
                            'summary' => 'A GUI for Vi',
                            'kind' => 'package',
                        ],
                        'vim' => [
                            'name' => 'vim',
                            'status' => 'not-installed',
                            'summary' => 'Vi IMproved',
                            'kind' => 'package',
                        ],
                    ],
                    'missing' => [],
                ],
                [],
                0,
                implode("\n", [
                    '<?xml version="1.0"?>',
                    '<stream>',
                    '<message type="info">Loading repository data...</message>',
                    '<message type="info">Reading installed packages...</message>',
                    '',
                    '<search-result version="0.0">',
                    '<solvable-list>',
                    '<solvable status="not-installed" name="gvim" summary="A GUI for Vi" kind="package" />',
                    '<solvable status="not-installed" name="vim" summary="Vi IMproved" kind="package" />',
                    '</solvable-list>',
                    '</search-result>',
                    '</stream>',
                ]),
                '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string> $packageNames
     *
     * @dataProvider casesParseMissing
     */
    public function testParseMissing(
        array $expected,
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): void {
        $parser = new SearchParser();
        $this->tester->assertSame(
            $expected,
            $parser->parseMissing($packageNames, $exitCode, $stdOutput, $stdError),
        );
    }
}
