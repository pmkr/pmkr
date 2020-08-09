<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager\Dnf;

use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Dnf\ListParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Dnf\ListParser
 */
class ListParserTest extends Unit
{

    protected UnitTester $tester;

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
                    'installed' => [
                        'openssl1.1' => [
                            'version' => '1:1.1.1l-1.fc36',
                            'architecture' => 'x86_64',
                            'name' => 'openssl1.1',
                            'status' => 'installed',
                        ],
                    ],
                    'not-installed' => [
                        'openssl1.1-devel' => [
                            'version' => '1:1.1.1l-1.fc36',
                            'architecture' => 'i686',
                            'name' => 'openssl1.1-devel',
                            'status' => 'not-installed',
                        ],
                    ],
                    'messages' => [],
                    'missing' => [
                        2 => 'not-exists',
                    ],
                ],
                [
                    'openssl1.1',
                    'openssl1.1-devel',
                    'not-exists',
                ],
                0,
                implode("\n", [
                    'Installed Packages',
                    'openssl1.1.x86_64        1:1.1.1l-1.fc36  @anaconda',
                    'Available Packages',
                    'openssl1.1.i686          1:1.1.1l-1.fc36  @anaconda',
                    'openssl1.1-devel.i686    1:1.1.1l-1.fc36  rawhide',
                    'openssl1.1-devel.x86_64  1:1.1.1l-1.fc36  rawhide',
                ]),
                '',
            ],
        ];
    }

    /**
     * @dataProvider casesParseMissing
     */
    public function testParseMissing(
        array $expected,
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): void {
        $config = $this->tester->grabConfig();
        $parser = new ListParser(new Utils($config));
        $this->tester->assertSame(
            $expected,
            $parser->parseMissing($packageNames, $exitCode, $stdOutput, $stdError),
        );
    }
}
