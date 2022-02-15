<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Model;

use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\Collection;
use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\Model\PmkrConfig
 */
class PmkrConfigTest extends Unit
{

    protected UnitTester $tester;

    public function testFoo(): void
    {
        $pmkr = $this->create([]);
        $this->tester->assertInstanceOf(Collection::class, $pmkr->patches);

        $pmkr = $this->create([
            'patches' => [
                'foo' => [
                    'weight' => 42,
                ],
            ],
        ]);
        $this->tester->assertInstanceOf(Collection::class, $pmkr->patches);
        $this->tester->assertCount(1, $pmkr->patches);
        $this->tester->assertArrayHasKey('foo', $pmkr->patches);
        $this->tester->assertInstanceOf(Patch::class, $pmkr->patches['foo']);
        $this->tester->assertSame(42, $pmkr->patches['foo']->weight);
    }

    /**
     * @param array<string, mixed> $configLayer
     *
     * @return \Pmkr\Pmkr\Model\PmkrConfig
     */
    protected function create(array $configLayer): PmkrConfig
    {
        return PmkrConfig::__set_state([
            'config' => $this->tester->grabConfig(null, $configLayer),
            'configPath' => [],
        ]);
    }
}
