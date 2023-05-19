<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OpSys;

use Codeception\Attribute\DataProvider;
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
            'match base - version_id available but not listed in the $identifiers' => [
                'arch',
                'arch_version_id_template',
                [
                    'a',
                    'opensuse-tumbleweed',
                    'ubuntu',
                    'arch',
                    'b',
                ],
            ],
        ];
    }

    /**
     * @param string[] $identifiers
     */
    #[DataProvider('casesPickOpSysIdentifier')]
    public function testPickOpSysIdentifier(?string$expected, string $opSysId, array $identifiers): void
    {
        $opSys = $this->tester->grabOpSys($opSysId);

        $this->tester->assertSame(
            $expected,
            $opSys->pickOpSysIdentifier($identifiers),
        );
    }
}
