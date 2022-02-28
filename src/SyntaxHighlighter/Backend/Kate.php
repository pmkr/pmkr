<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter\Backend;

use Pmkr\Pmkr\Util\ProcessFactory;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Process\Process;

class Kate extends Base
{

    /**
     * {@inheritdoc}
     */
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

    protected ProcessFactory $processFactory;

    public function __construct(
        Utils $utils,
        ProcessFactory $processFactory
    ) {
        parent::__construct($utils);
        $this->processFactory = $processFactory;
    }

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

        $process = $this->processFactory->fromShellCommandline($command);
        $process->setTimeout(120);
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
            $process->setTimeout(120);
            $exitCode = $process->run(null, $this->envVars);
            if ($exitCode !== 0) {
                return [];
            }

            $stdOutput = trim($process->getOutput());
            $languages = $stdOutput === '' ?
                [] :
                (preg_split('/\s*\n\s*/', $stdOutput) ?: []);
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
