<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputFormatter;

use Codeception\Stub\Expected;
use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Pmkr\Pmkr\OutputFormatter\YamlFormatter;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;

/**
 * @covers \Pmkr\Pmkr\OutputFormatter\YamlFormatter
 * @covers \Pmkr\Pmkr\OutputFormatter\AnsiFormatterTrait
 */
class YamlFormatterTest extends TestBase
{

    /**
     * {@inheritdoc}
     */
    public function casesWrite(): array
    {
        return [
            'decorated false' => [
                "{  }\n",
                [],
                [
                    'decorated' => false,
                ],
            ],
            'decorated true' => [
                'highlighted',
                [],
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
        $params = [];
        if (!empty($outputOptions['decorated'])) {
            $params['highlight'] = Expected::atLeastOnce('highlighted');
        }

        $highlighter = $this->make(SyntaxHighlighter::class, $params);

        return new YamlFormatter($highlighter);
    }
}
