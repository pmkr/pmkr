<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter\Backend;

use Symfony\Component\Process\Process;

class Kate extends Base
{
    protected array $executable = [
        'kate-syntax-highlighter',
    ];

    /**
     * {@inheritdoc}
     */
    protected array $outputFormatMapping = [
        'html' => 'html',
        'ansi' => 'ansi256Colors',
    ];

    public function highlight(
        string $code,
        ?string $externalLanguage = null,
        ?string $externalTheme = null,
        ?string $outputFormat = 'ansi'
    ): string {
        $internalLanguage = $this->getInternalLanguage($externalLanguage);
        $internalTheme = $this->getInternalTheme($externalTheme);
        $internalOutputFormat = $this->getInternalOutputFormat($outputFormat);
        $command = implode(' ', [
            'echo',
            '-n',
            '"${code}"',
            '|',
            implode(' ', $this->executable),
            '--stdin',
            '--output-format="${internalOutputFormat}"',
            '--theme="${internalTheme}"',
            '--syntax="${internalLanguage}"',
        ]);

        // @todo Get it from helperSet().
        $process = Process::fromShellCommandline($command);
        $exitCode = $process->run(
            null,
            [
                'code' => $code,
                'internalTheme' => $internalTheme,
                'internalLanguage' => (string) $internalLanguage,
                'internalOutputFormat' => (string) $internalOutputFormat,
            ],
        );
        if ($exitCode !== 0) {
            return $code;
        }

        return $process->getOutput();
    }

    protected function getInternalLanguages(): array
    {
        if ($this->internalLanguages === null) {
            $command = array_merge(
                $this->executable,
                [
                    '--list',
                ],
            );

            $process = Process::fromShellCommandline(implode(' ', $command));
            $exitCode = $process->run(null, $this->envVars);
            if ($exitCode !== 0) {
                return [];
            }

            $stdOutput = trim($process->getOutput());
            $languages = $stdOutput === '' ? [] : preg_split('/\s*\n\s*/', $stdOutput);
            $this->internalLanguages = array_fill_keys(
                $languages,
                [
                    'patterns' => [],
                ],
            );
        }

        return $this->internalLanguages;
    }
}
