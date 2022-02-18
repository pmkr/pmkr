<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OpSys;

use Codeception\Test\Unit;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\OpSys\OpSys
 */
class OpSysTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesPickOpSysIdentifier(): array
    {
        return [
            'empty' => [
                null,
                'opensuse-tumbleweed',
                [],
            ],
            'not found' => [
                null,
                'opensuse-tumbleweed',
                [
                    'a',
                    'b',
                ],
            ],
            'match opensuse-tumbleweed' => [
                'opensuse-tumbleweed',
                'opensuse-tumbleweed',
                [
                    'a',
                    'opensuse-tumbleweed',
                    'ubuntu-21-10',
                    'b',
                ],
            ],
            'match ubuntu-21-10' => [
                'ubuntu-21-10',
                'ubuntu-21-10',
                [
                    'a',
                    'opensuse-tumbleweed',
                    'ubuntu-22-04',
                    'ubuntu-21-04',
                    'ubuntu-21-10',
                    'b',
                ],
            ],
            'match ubuntu-21-10 base' => [
                'ubuntu',
                'ubuntu-21-10',
                [
                    'a',
                    'opensuse-tumbleweed',
                    'ubuntu',
                    'b',
                ],
            ],
        ];
    }

    /**
     * @param string[] $identifiers
     *
     * @dataProvider casesPickOpSysIdentifier
     */
    public function testPickOpSysIdentifier(?string$expected, string $opSysId, array $identifiers): void
    {
        $opSys = $this->tester->grabOpSys($opSysId);

        $this->tester->assertSame(
            $expected,
            $opSys->pickOpSysIdentifier($identifiers),
        );
    }
}
