<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Model;

use Codeception\Test\Unit;
use Consolidation\Config\Config;
use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\Model\Patch
 * @covers \Pmkr\Pmkr\Model\Checksum
 * @covers \Pmkr\Pmkr\Model\Base
 */
class PatchTest extends Unit
{
    protected UnitTester $tester;

    public function testSomeFeature(): void
    {
        $path = [];
        $data = [
            'enabled' => true,
            'weight' => 41,
            'issue' => 'my_issue',
            'description' => 'my_desc',
            'uri' => 'my_uri',
            'checksum' => [
                'hashChecksum' => 'my_hashChecksum',
                'hashAlgorithm' => 'my_hashAlgorithm',
                'hashFlags' => 44,
                'hashKey' => 'my_hashKey',
            ],
        ];
        $config = new Config($data);
        $patch = Patch::__set_state([
            'config' => $config,
            'configPath' => $path,
        ]);
        $this->tester->assertSame($data['enabled'], $patch->enabled);
        $this->tester->assertSame($data['weight'], $patch->weight);
        $this->tester->assertSame($data['issue'], $patch->issue);
        $this->tester->assertSame($data['description'], $patch->description);
        $this->tester->assertSame($data['uri'], $patch->uri);
        $this->tester->assertSame($data['checksum']['hashChecksum'], $patch->checksum->hashChecksum);
        $this->tester->assertSame($data['checksum']['hashAlgorithm'], $patch->checksum->hashAlgorithm);
        $this->tester->assertSame($data['checksum']['hashFlags'], $patch->checksum->hashFlags);
        $this->tester->assertSame($data['checksum']['hashKey'], $patch->checksum->hashKey);
    }
}
