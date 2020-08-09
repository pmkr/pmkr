<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

class YamlFormatter extends \Consolidation\OutputFormatters\Formatters\YamlFormatter
{
    use AnsiFormatterTrait;

    protected function getExternalLanguage(): string
    {
        return 'yaml';
    }
}
