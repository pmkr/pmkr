<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter;

interface BackendInterface
{

    public function isAvailable(string $outputFormat, string $externalLanguage): bool;

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options);

    public function highlight(
        string $code,
        ?string $externalLanguage = null,
        ?string $externalTheme = null,
        ?string $outputFormat = 'ansi'
    ): string;
}
