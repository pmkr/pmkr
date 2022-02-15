<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Test\Unit;
use Pmkr\Pmkr\ProcessResultParser\TerminalColorParser;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\ProcessFactory;
use Pmkr\Pmkr\Util\TerminalColorSchemeDetector;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;

/**
 * @covers \Pmkr\Pmkr\Util\TerminalColorSchemeDetector
 * @covers \Pmkr\Pmkr\Util\ProcessFactory
 */
class TerminalColorSchemeDetectorTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesGetTheme(): array
    {
        return [
            'env var dark' => [
                'dark',
                [
                    'env' => [
                        'COLORFGBG' => '15;0',
                    ],
                ],
                null,
            ],
            'env var light' => [
                'light',
                [
                    'env' => [
                        'COLORFGBG' => '0;15',
                    ],
                ],
                null,
            ],
            'process dark' => [
                'dark',
                [],
                [
                    'exitCode' => 0,
                    'stdOutput' => '11;rgb:0000/0000/0000',
                    'stdError' => '',
                ],
            ],
            'process light' => [
                'light',
                [],
                [
                    'exitCode' => 0,
                    'stdOutput' => '11;rgb:ffff/ffff/ffff',
                    'stdError' => '',
                ],
            ],
            'process fail' => [
                null,
                [],
                [
                    'exitCode' => 1,
                    'stdOutput' => '11;rgb:ffff/ffff/ffff',
                    'stdError' => '',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configLayer
     * @param null|array{
     *     exitCode: int,
     *     stdOutput: string,
     *     stdError: string,
     * } $processResult
     *
     * @dataProvider casesGetTheme
     */
    public function testGetTheme(
        ?string $expected,
        array $configLayer,
        ?array $processResult
    ): void {
        if ($processResult !== null) {
            DummyProcess::$prophecy[] = $processResult;
        }

        $config = $this->tester->grabConfig(null, $configLayer);
        $processFactory = new ProcessFactory();
        $processFactory->setClassFqn(DummyProcess::class);
        $parser = new TerminalColorParser();
        $detector = new TerminalColorSchemeDetector($config, $processFactory, $parser);

        $this->tester->assertEquals($expected, $detector->getTheme());
    }
}
