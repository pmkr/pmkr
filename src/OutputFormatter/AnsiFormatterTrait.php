<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

use Consolidation\OutputFormatters\Options\FormatterOptions;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait AnsiFormatterTrait
{
    protected SyntaxHighlighter $syntaxHighlighter;

    public function __construct(SyntaxHighlighter $syntaxHighlighter)
    {
        $this->syntaxHighlighter = $syntaxHighlighter;
    }

    /**
     * @see \Consolidation\OutputFormatters\Formatters\FormatterInterface::write
     *
     * @return void
     */
    public function write(
        OutputInterface $output,
        $data,
        FormatterOptions $options,
    ) {
        if (!$output->isDecorated()) {
            parent::write($output, $data, $options);

            return;
        }

        $buffered = new BufferedOutput();
        parent::write($buffered, $data, $options);
        $output->write(
            $this
                ->syntaxHighlighter
                ->highlight(
                    $buffered->fetch(),
                    'ansi',
                    $this->getExternalLanguage(),
                )
        );
    }

    abstract protected function getExternalLanguage(): string;
}
