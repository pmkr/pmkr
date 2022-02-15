<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\Util\TerminalColorSchemeDetector;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class SyntaxHighlighter implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected TerminalColorSchemeDetector $terminalColorSchemeDetector;

    protected string $theme = 'auto';

    /**
     * Mapping theme auto-detection result with external theme name.
     *
     * Key auto-detection result.
     * Value external theme name.
     *
     * @var array<string, string>
     */
    protected array $themeAutoMapping = [
        '_default' => 'dark',
        'dark' => 'dark',
        'light' => 'light',
    ];

    /**
     * Language and handler mapping.
     *
     * Top-level key is the output format.
     *
     * @var array{
     *     ansi: array<
     *         string,
     *         array<
     *             string,
     *             array{
     *                 enabled?: bool,
     *                 weight?: int|float,
     *             }
     *         >
     *     >,
     * }
     */
    protected array $languageMapping = [
        'ansi' => [],
    ];

    public function __construct(TerminalColorSchemeDetector $terminalColorSchemeDetector)
    {
        $this->terminalColorSchemeDetector = $terminalColorSchemeDetector;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('theme', $options)) {
            $this->theme = $options['theme'];
        }

        if (array_key_exists('themeAutoMapping', $options)) {
            $this->themeAutoMapping = $options['themeAutoMapping'];
        }

        if (array_key_exists('languageMapping', $options)) {
            $this->languageMapping = $options['languageMapping'];
        }

        return $this;
    }

    public function highlight(
        string $code,
        string $outputFormat = 'ansi',
        ?string $externalLanguage = null,
        ?string $externalTheme = null
    ): string {
        $handler = $this->getHandler($outputFormat, $externalLanguage);
        if (!$handler) {
            return $code;
        }

        if ($externalTheme === null || $externalTheme === 'auto') {
            $autoTheme = $this->terminalColorSchemeDetector->getTheme();
            $externalTheme = $this->themeAutoMapping[$autoTheme]
                ?? $this->themeAutoMapping['_default']
                ?? 'dark';
        }

        return $handler->highlight($code, $externalLanguage, $externalTheme, $outputFormat);
    }

    protected function getHandler(
        string $outputFormat = 'ansi',
        ?string $externalLanguage = null
    ): ?BackendInterface {
        $handlerCandidates = $this->languageMapping[$outputFormat][$externalLanguage]
            ?? $this->languageMapping[$outputFormat]['_default']
            ?? [];

        $handlerCandidates = array_filter($handlerCandidates, (new ArrayFilterEnabled()));
        uasort($handlerCandidates, (new ArrayValueComparer(['weight' => 50])));

        $container = $this->getContainer();
        foreach (array_keys($handlerCandidates) as $handlerName) {
            $instance = $container->get("syntax_highlighter.handler.$handlerName");
            if ($instance->isAvailable($outputFormat, $externalLanguage)) {
                return $instance;
            }
        }

        return null;
    }
}
