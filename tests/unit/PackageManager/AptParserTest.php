<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\PackageManager;

use Codeception\Test\Unit;
use Pmkr\Pmkr\PackageManager\Apt;
use Pmkr\Pmkr\PackageManager\Apt\ListParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\PackageManager\Apt
 */
class AptParserTest extends Unit
{

    protected UnitTester $tester;

    public function testMissingCommand(): void
    {
        $utils = new Utils($this->tester->grabConfig());
        $parser = new ListParser($utils);
        $pm = new Apt($parser);
        $expected = "apt -qq list 'a' 'b' 2>/dev/null | cat";
        $packageNames = ['a', 'b'];
        $this->tester->assertSame($expected, $pm->missingCommand($packageNames));
        $this->tester->assertSame('', $pm->missingCommand([]));
    }

    public function testInstallCommand(): void
    {
        $utils = new Utils($this->tester->grabConfig());
        $parser = new ListParser($utils);
        $pm = new Apt($parser);
        $expected = "apt-get install -y 'a' 'b'";
        $packageNames = ['a', 'b'];
        $this->tester->assertSame($expected, $pm->installCommand($packageNames));
        $this->tester->assertSame('', $pm->installCommand([]));
    }
}
