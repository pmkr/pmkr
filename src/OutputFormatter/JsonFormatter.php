<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

class JsonFormatter extends \Consolidation\OutputFormatters\Formatters\JsonFormatter
{
    use AnsiFormatterTrait;

    protected function getExternalLanguage(): string
    {
        return 'json';
    }
}
