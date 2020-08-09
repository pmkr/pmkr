<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Model;

use Codeception\Test\Unit;
use Consolidation\Config\Config;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\Model\Instance<extended>
 */
class InstanceTest extends Unit
{
    protected UnitTester $tester;
    
    public function testSomeFeature()
    {
        $data = [
            'instances' => [
                'my01' => [
                    'coreVersion' => '1.2.3',
                ],
            ],
        ];

        $config = $this->tester->grabConfig(null, $data);
        $instance = Instance::__set_state([
            'config' => $config,
            'configPath' => ['instances', 'my01'],
        ]);

        $this->tester->assertFalse($instance->isZts);

        $config->set('instances.my01.isZts', true);
        $this->tester->assertTrue($instance->isZts);
    }
}
