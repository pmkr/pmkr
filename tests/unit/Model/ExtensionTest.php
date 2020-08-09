<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Model;

use Codeception\Test\Unit;
use Consolidation\Config\Config;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\Model\Extension<extended>
 * @covers \Pmkr\Pmkr\Model\Patches<extended>
 */
class ExtensionTest extends Unit
{
    protected UnitTester $tester;
    
    public function testSomeFeature()
    {
        $path = [];
        $data = [
            'patches' => [
                'foo' => true,
            ],
        ];
        $config = new Config($data);
        $extension = Extension::__set_state([
            'config' => $config,
            'configPath' => $path,
        ]);

        $this->tester->assertSame(
            $data['patches']['foo'],
            $extension->patches['foo'],
        );
    }
}
