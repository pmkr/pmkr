<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Pmkr\Pmkr\Util\ProcessFactory;
use Pmkr\Pmkr\Util\ShellHelper;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Tests\UnitTester;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;

/**
 * @covers \Pmkr\Pmkr\Util\ShellHelper
 */
class ShellHelperTest extends Unit
{

    protected UnitTester $tester;

    /**
     * @return void
     */
    protected function _before()
    {
        parent::_before();
        DummyProcess::reset();
    }

    /**
     * @return array<string, mixed>
     */
    public function casesCollectPhpIniPaths(): array
    {
        return [
            'empty' => [
                [
                    'return' => [],
                    'command' => implode(' ', [
                        "'bash'",
                        "'-c'",
                        "'( unset PHPRC PHP_INI_SCAN_DIR ; LANGUAGE='\''en_GB:en_US'\'' '\''/path/to/php'\'' -i )'",
                    ]),
                ],
                [
                    'stdOutput' => '',
                    'stdError' => '',
                    'exitCode' => 0,
                ],
            ],
            'basic' => [
                [
                    'return' => [
                        'PHPRC' => 'my-php.ini',
                        'PHP_INI_SCAN_DIR' => '/path/to/extra/ini',
                    ],
                    'command' => implode(' ', [
                        "'bash'",
                        "'-c'",
                        "'( unset PHPRC PHP_INI_SCAN_DIR ; LANGUAGE='\''en_GB:en_US'\'' '\''/path/to/php'\'' -i )'",
                    ]),
                ],
                [
                    'stdOutput' => implode("\n", [
                        'foo',
                        'Loaded Configuration File => my-php.ini',
                        'bar',
                        'Scan this dir for additional .ini files => /path/to/extra/ini',
                        'baz',
                        '',
                    ]),
                    'stdError' => '',
                    'exitCode' => 0,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     * @param null|DevProcessResult $processResult
     *
     * @dataProvider casesCollectPhpIniPaths
     */
    public function testCollectPhpIniPaths(
        array $expected,
        ?array $processResult,
        string $phpBinary = '/path/to/php'
    ): void {
        if ($processResult !== null) {
            DummyProcess::$prophecy[] = $processResult;
        }

        $shellHelper = $this->createShellHelper();
        $this->tester->assertSame(
            $expected['return'],
            $shellHelper->collectPhpIniPaths($phpBinary),
        );

        $this->tester->assertCount(1, DummyProcess::$instances);
        $this->tester->assertSame(
            $expected['command'],
            DummyProcess::$instances[0]->getCommandLine(),
        );
    }

    protected function createShellHelper(): ShellHelper
    {
        $processFactory = new ProcessFactory();
        $processFactory->setClassFqn(DummyProcess::class);

        return new ShellHelper($processFactory);
    }
}
