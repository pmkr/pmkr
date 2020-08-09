<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\ProcessResultParser;

use Pmkr\Pmkr\ProcessResultParser\ParserInterface;
use Pmkr\Pmkr\ProcessResultParser\TerminalColorParser;

/**
 * @covers \Pmkr\Pmkr\ProcessResultParser\TerminalColorParser<extended>
 */
class TerminalColorParserTest extends TestBase
{

    protected function createParser(): ParserInterface
    {
        return new TerminalColorParser();
    }

    public function casesParser(): array
    {
        return [
            'c-0 o-empty e-empty' => [
                [
                    'exitCode' => 0,
                    'assets' => [],
                ],
                0,
                '',
                '',
            ],
            'c-0 o-basic e-empty' => [
                [
                    'exitCode' => 0,
                    'assets' => [
                        'color' => '11',
                        'rgb_16' => [
                            'r' => '12ab',
                            'g' => '34cd',
                            'b' => '46ef',
                        ],
                        'rgb_10' => [
                            'r' => '4779',
                            'g' => '13517',
                            'b' => '18159',
                        ],
                    ],
                ],
                0,
                '11;rgb:12ab/34cd/46ef',
                '',
            ],
        ];
    }
}
