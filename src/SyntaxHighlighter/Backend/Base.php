<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter\Backend;

use Pmkr\Pmkr\SyntaxHighlighter\BackendInterface;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Process\Process;

abstract class Base implements BackendInterface
{
    protected Utils $utils;

    /**
     * @var array<string, string>
     */
    protected array $envVars = [];

    /**
     * @var array<string>
     */
    protected array $executable = [];

    /**
     * @var array<string, string>
     */
    protected array $themeMapping = [];

    protected string $defaultTheme = '';

    /**
     * Key: external; value: internal;
     *
     * @var string[]
     */
    protected array $languageMapping = [];

    /**
     * Key: external; value: internal;
     *
     * @var array<string, string>
     */
    protected array $outputFormatMapping = [
        'html' => 'html',
        'ansi' => 'ansi',
    ];

    protected ?bool $isExecutable = null;

    /**
     * @var ?array<
     *     string,
     *     array{
     *         patterns?: array<string>,
     *     }
     * >
     */
    protected ?array $internalLanguages = null;

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('themeMapping', $options)) {
            $this->themeMapping = $options['themeMapping'];
        }

        if (array_key_exists('languageMapping', $options)) {
            $this->languageMapping = $options['languageMapping'];
        }

        if (array_key_exists('defaultTheme', $options)) {
            $this->defaultTheme = $options['defaultTheme'];
        }

        if (array_key_exists('envVars', $options)) {
            $this->envVars = $options['envVars'];
        }

        if (array_key_exists('executable', $options)) {
            $this->executable = $options['executable'];
        }

        return $this;
    }

    public function isAvailable(string $outputFormat, string $externalLanguage): bool
    {
        return
            $this->isExecutable()
            && array_key_exists(
                $this->getInternalLanguage($externalLanguage),
                $this->getInternalLanguages(),
            );
    }

    protected function isExecutable(): bool
    {
        // @todo Re-think $this->executable.
        if ($this->isExecutable === null) {
            $command = [
                'bash',
                '-c',
                implode(' ', $this->executable) . ' --help',
            ];
            $process = new Process($command);
            $this->isExecutable = $process->run() === 0;
        }

        return $this->isExecutable;
    }

    /**
     * @return array<
     *     string,
     *     array{
     *         patterns?: array<string>,
     *     }
     * >
     */
    abstract protected function getInternalLanguages(): array;

    protected function getInternalLanguage(string $externalLanguage): ?string
    {
        return $this->languageMapping[$externalLanguage]
            ?? null;
    }

    protected function getInternalTheme(?string $externalTheme = null): string
    {
        return $this->themeMapping[$externalTheme] ?? $this->defaultTheme;
    }

    protected function getInternalOutputFormat(?string $externalOutputFormat = null): ?string
    {
        return $this->outputFormatMapping[$externalOutputFormat] ?? null;
    }
}
