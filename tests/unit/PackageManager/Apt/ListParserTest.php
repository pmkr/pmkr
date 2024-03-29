<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager\Apt;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Apt\ListParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Apt\ListParser
 */
class ListParserTest extends Unit
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
                    'installed' => [
                        'libcurl4' => [
                            'name' => 'libcurl4',
                            'type' => 'impish,now',
                            'version' => '7.74.0-1.3ubuntu2',
                            'architecture' => 'amd64',
                            'status' => [
                                'installed',
                                'automatic',
                            ],
                        ],
                    ],
                    'not-installed' => [
                        'ruby-curb' => [
                            'name' => 'ruby-curb',
                            'type' => 'impish',
                            'version' => '0.9.11-1',
                            'architecture' => 'amd64',
                            'status' => [],
                        ],
                        'my-01' => [
                            'name' => 'my-01',
                            'type' => 'jammy-updates,jammy-security',
                            'version' => '7.81.0-1ubuntu1.13',
                            'architecture' => 'amd64',
                            'status' => [
                                'upgradable from: 7.81.0-1ubuntu1.10',
                            ],
                        ],
                    ],
                    'missing' => [
                        2 => 'not-exists',
                    ],
                ],
                [
                    'libcurl4',
                    'ruby-curb',
                    'not-exists',
                ],
                0,
                implode("\n", [
                    'libcurl4/impish,now 7.74.0-1.3ubuntu2 amd64 [installed,automatic]',
                    'ruby-curb/impish 0.9.11-1 amd64',
                    'my-01/jammy-updates,jammy-security 7.81.0-1ubuntu1.13 amd64 [upgradable from: 7.81.0-1ubuntu1.10]',
                    '',
                ]),
                '',
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
        string $stdError,
    ): void {
        $config = $this->tester->grabConfig();
        $parser = new ListParser(new Utils($config));
        $this->tester->assertSame(
            $expected,
            $parser->parseMissing($packageNames, $exitCode, $stdOutput, $stdError),
        );
    }
}
