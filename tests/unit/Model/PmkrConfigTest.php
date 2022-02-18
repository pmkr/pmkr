<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Model;

use Codeception\Test\Unit;
use Pmkr\Pmkr\Model\Collection;
use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Model\Variation;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\Model\PmkrConfig<extended>
 */
class PmkrConfigTest extends Unit
{

    protected UnitTester $tester;

    public function testPatches(): void
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

    public function testDefaultVariation(): void
    {
        $pmkr = $this->create([]);
        $this->tester->assertSame(null, $pmkr->defaultVariation);

        $pmkr = $this->create([
            'defaultVariationKey' => 'v1',
        ]);
        $this->tester->assertSame('v1', $pmkr->defaultVariationKey);
        $this->tester->assertSame(null, $pmkr->defaultVariation);

        $pmkr = $this->create([
            'extensions' => [],
            'extensionSets' => [],
            'cores' => [
                '0704-nts' => [
                    '0704-nts' => '',
                ],
            ],
            'instances' => [
                'i1' => [
                    'key' => 'i1',
                    'coreVersion' => '7.4.0',
                ],
            ],
            'variations' => [
                'v1' => [
                    'key' => 'v0',
                ],
            ],
            'defaultVariationKey' => 'v1',
        ]);
        $this->tester->assertSame('v1', $pmkr->defaultVariationKey);
        $this->tester->assertInstanceOf(Variation::class, $pmkr->defaultVariation);
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
