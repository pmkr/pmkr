<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\Validate\ValidationInterface;
use Pmkr\Pmkr\VariationPickResult\VariationPickCommandResult;
use Pmkr\Pmkr\VariationPickResult\VariationPickResult;
use Pmkr\Pmkr\VariationPickResult\VariationPickResultConverter;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;
use Symfony\Component\Console\Output\OutputInterface;

class ShellExecutableFormatter implements FormatterInterface, ValidationInterface
{
    protected VariationPickResultConverter $converter;

    protected SyntaxHighlighter $syntaxHighlighter;

    public function __construct(
        VariationPickResultConverter $converter,
        SyntaxHighlighter $syntaxHighlighter,
    ) {
        $this->converter = $converter;
        $this->syntaxHighlighter = $syntaxHighlighter;
    }

    /**
     * @param \ReflectionClass<object> $dataType
     *
     * @return bool
     */
    public function isValidDataType(\ReflectionClass $dataType)
    {
        return $dataType->getName() === VariationPickCommandResult::class
            || $dataType->isSubclassOf(VariationPickCommandResult::class)
            || $dataType->getName() === VariationPickResult::class;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($structuredData)
    {
        return $structuredData;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function write(
        OutputInterface $output,
        $data,
        FormatterOptions $options,
    ) {
        $code = $this->converter->toShellExecutable($data);
        if ($code === null) {
            return;
        }

        if ($output->isDecorated()) {
            $code = $this->syntaxHighlighter->highlight($code, 'ansi', 'bash');
        }

        $output->write($code);
    }
}
