<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\ProcessResultParser;

use Pmkr\Pmkr\ProcessResultParser\BatListLanguagesParser;
use Pmkr\Pmkr\ProcessResultParser\ParserInterface;
use Pmkr\Pmkr\Utils;

/**
 * @covers \Pmkr\Pmkr\ProcessResultParser\BatListLanguagesParser<extended>
 */
class BatListLanguagesParserTest extends TestBase
{

    protected function createParser(): ParserInterface
    {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);

        return new BatListLanguagesParser($utils);
    }

    /**
     * {@inheritdoc}
     */
    public function casesParser(): array
    {
        return [
            'c-0 o-empty e-empty' => [
                [
                    'exitCode' => 0,
                    'assets' => [
                        'languages' => [],
                    ],
                ],
                0,
                '',
                '',
            ],
            'c-0 o-basic e-empty' => [
                [
                    'exitCode' => 0,
                    'assets' => [
                        'languages' => [
                            'PHP' => [
                                'patterns' => [
                                    'php',
                                    'php3',
                                    'php4',
                                    'php5',
                                    'php7',
                                    'phps',
                                    'phpt',
                                    'phtml',
                                ],
                            ],
                            'Plain Text' => [
                                'patterns' => [
                                    'txt',
                                ],
                            ],
                        ],
                    ],
                ],
                0,
                implode("\n", [
                    'PHP:php,php3,php4,php5,php7,phps,phpt,phtml',
                    'Plain Text:txt',
                ]),
                '',
            ],
        ];
    }
}
