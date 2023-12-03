<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager\Apk;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Apk\ListParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Apk\ListParser
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
                        'php8' => [
                            'id' => 'php8-8.0.16-r0',
                            'architecture' => 'x86_64',
                            'name' => 'php8',
                            'version' => '8.0.16-r0',
                            'license' => [
                                'PHP-3.01',
                                'BSD-3-Clause',
                                'LGPL-2.0-or-later',
                                'MIT',
                                'Zend-2.0',
                            ],
                            'status' => [
                                'installed',
                            ],
                        ],
                    ],
                    'not-installed' => [
                        'g++' => [
                            'id' => 'g++-10.3.1_git20211027-r0',
                            'architecture' => 'x86_64',
                            'name' => 'g++',
                            'version' => '10.3.1_git20211027-r0',
                            'license' => [
                                'GPL-2.0-or-later',
                                'LGPL-2.1-or-later',
                            ],
                            'status' => [
                                'not-installed',
                            ],
                        ],
                        'vim' => [
                            'id' => 'vim-8.2.4173-r0',
                            'architecture' => 'x86_64',
                            'name' => 'vim',
                            'version' => '8.2.4173-r0',
                            'license' => [
                                'Vim',
                            ],
                            'status' => [
                                'not-installed',
                            ],
                        ],
                    ],
                    'missing' => [
                        3 => 'not-exists',
                    ],
                ],
                [
                    'g++',
                    'php8',
                    'vim',
                    'not-exists',
                ],
                0,
                implode("\n", [
                    // Actually there is {gcc} instead of {g++}.
                    'g++-10.3.1_git20211027-r0 x86_64 {gcc} (GPL-2.0-or-later LGPL-2.1-or-later)',
                    'php8-8.0.16-r0 x86_64 {php8} (PHP-3.01 BSD-3-Clause LGPL-2.0-or-later MIT Zend-2.0) [installed]',
                    'vim-8.2.4173-r0 x86_64 {vim} (Vim)',
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
