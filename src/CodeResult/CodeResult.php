<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\CodeResult;

class CodeResult
{
    /**
     * From the perspective of a \Pmkr\Pmkr\SyntaxHighlighter\BackendInterface
     * this is the externalLanguage.
     */
    public ?string $language = null;

    public string $fileName = '';

    public string $code = '';
}
