<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputFormatter;

use Codeception\Stub\Expected;
use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Pmkr\Pmkr\CodeResult\CodeResult;
use Pmkr\Pmkr\OutputFormatter\CodeFormatter;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;

/**
 * @covers \Pmkr\Pmkr\OutputFormatter\CodeFormatter
 */
class CodeFormatterTest extends TestBase
{

    /**
     * {@inheritdoc}
     */
    public function casesWrite(): array
    {
        $codeResult1 = new CodeResult();
        $codeResult1->fileName = 'foo.php';
        $codeResult1->language = 'php';
        $codeResult1->code = '<?php';

        $codeResult2 = clone $codeResult1;
        $codeResult3 = clone $codeResult1;
        $codeResult3->code = '';

        return [
            'empty' => [
                '',
                $codeResult3,
                [
                    'decorated' => false,
                ],
            ],
            'decorated false' => [
                '<?php',
                $codeResult1,
                [
                    'decorated' => false,
                ],
            ],
            'decorated true' => [
                'highlighted',
                $codeResult2,
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

        return new CodeFormatter($highlighter);
    }
}
