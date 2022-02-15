<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

use Consolidation\OutputFormatters\Exception\IncompatibleDataException;
use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\Validate\ValidationInterface;
use Pmkr\Pmkr\CodeResult\CodeCommandResult;
use Pmkr\Pmkr\CodeResult\CodeResult;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;
use Symfony\Component\Console\Output\OutputInterface;

class CodeFormatter implements FormatterInterface, ValidationInterface
{
    protected SyntaxHighlighter $syntaxHighlighter;

    public function __construct(
        SyntaxHighlighter $syntaxHighlighter
    ) {
        $this->syntaxHighlighter = $syntaxHighlighter;
    }

    /**
     * @param \ReflectionClass<object> $dataType
     *
     * @return bool
     */
    public function isValidDataType(\ReflectionClass $dataType)
    {
        return $dataType->getName() === CodeCommandResult::class
            || $dataType->isSubclassOf(CodeCommandResult::class)
            || $dataType->getName() === CodeResult::class
            || $dataType->isSubclassOf(CodeResult::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function validate($structuredData)
    {
        if (!($structuredData instanceof CodeResult)) {
            throw new IncompatibleDataException(
                $this,
                $structuredData,
                [
                    CodeResult::class,
                ],
            );
        }

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
        FormatterOptions $options
    ) {
        /** @var \Pmkr\Pmkr\CodeResult\CodeResult $data */
        $code = $data->code;
        if ($code === '') {
            return;
        }

        if ($output->isDecorated()) {
            $code = $this->syntaxHighlighter->highlight($code, 'ansi', $data->language);
        }

        $output->write($code);
    }
}
