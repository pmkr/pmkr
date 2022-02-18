<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Pmkr\Pmkr\Util\ProcessFactory;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Tests\UnitTester;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Symfony\Component\Process\Process;

/**
 * @covers \Pmkr\Pmkr\Util\ProcessFactory
 */
class ProcessFactoryTest extends Unit
{

    protected UnitTester $tester;

    public function testCreate(): void
    {
        $pf = new ProcessFactory();
        $this->tester->assertSame(Process::class, $pf->getClassFqn());
        $this->tester->assertInstanceOf(
            Process::class,
            $pf->createInstance([]),
        );
        $this->tester->assertInstanceOf(
            Process::class,
            $pf->fromShellCommandline(''),
        );

        $pf = new ProcessFactory();
        $pf->setClassFqn(DummyProcess::class);
        $this->tester->assertSame(DummyProcess::class, $pf->getClassFqn());
        $this->tester->assertInstanceOf(
            DummyProcess::class,
            $pf->createInstance([]),
        );
        $this->tester->assertInstanceOf(
            DummyProcess::class,
            $pf->fromShellCommandline(''),
        );
    }
}
