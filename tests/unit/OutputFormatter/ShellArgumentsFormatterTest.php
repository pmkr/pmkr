<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputFormatter;

use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Pmkr\Pmkr\OutputFormatter\ShellArgumentsFormatter;
use Pmkr\Pmkr\Tests\UnitTester;

/**
 * @covers \Pmkr\Pmkr\OutputFormatter\ShellArgumentsFormatter
 */
class ShellArgumentsFormatterTest extends TestBase
{
    public UnitTester $tester;

    /**
     * {@inheritdoc}
     */
    public function casesWrite(): array
    {
        return [
            'empty' => [
                '',
                [],
                [
                    'decorated' => false,
                ],
            ],
            'decorated false' => [
                'a b c d',
                [
                    'a',
                    'b',
                    'c d',
                ],
                [
                    'decorated' => false,
                ],
            ],
            'decorated true' => [
                'a b c d',
                [
                    'a',
                    'b',
                    'c d',
                ],
                [
                    'decorated' => true,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter(array $outputOptions): FormatterInterface
    {
        return new ShellArgumentsFormatter();
    }
}
