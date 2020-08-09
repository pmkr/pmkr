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

class ShellVarSetterFormatter implements FormatterInterface, ValidationInterface
{
    protected VariationPickResultConverter $converter;

    protected SyntaxHighlighter $syntaxHighlighter;

    public function __construct(
        VariationPickResultConverter $converter,
        SyntaxHighlighter $syntaxHighlighter
    ) {
        $this->converter = $converter;
        $this->syntaxHighlighter = $syntaxHighlighter;
    }

    public function isValidDataType(\ReflectionClass $dataType)
    {
        return $dataType->getName() === VariationPickCommandResult::class
            || $dataType->isSubclassOf(VariationPickCommandResult::class)
            || $dataType->getName() === VariationPickResult::class;
    }

    public function validate($structuredData)
    {
        return $structuredData;
    }

    public function write(
        OutputInterface $output,
        $data,
        FormatterOptions $options
    ) {
        $code = $this->converter->toShellVarSetter($data);
        if ($output->isDecorated()) {
            $code = $this->syntaxHighlighter->highlight($code, 'ansi', 'bash');
        }

        $output->write($code);
    }
}
