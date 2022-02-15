<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter\Backend;

use Pmkr\Pmkr\ProcessResultParser\BatListLanguagesParser;
use Pmkr\Pmkr\Util\ProcessFactory;
use Pmkr\Pmkr\Utils;

class Bat extends Base
{

    /**
     * {@inheritdoc}
     */
    protected array $executable = [
        'bat',
    ];

    protected ProcessFactory $processFactory;

    /**
     * {@inheritdoc}
     */
    protected array $languageMapping = [
        'json' => 'JSON',
        'yaml' => 'YAML',
        'zsh' => 'Bourne Again Shell (bash)',
        'bash' => 'Bourne Again Shell (bash)',
    ];

    protected BatListLanguagesParser $listLanguagesParser;

    public function __construct(
        Utils $utils,
        ProcessFactory $processFactory,
        BatListLanguagesParser $listLanguagesParser
    ) {
        parent::__construct($utils);
        $this->processFactory = $processFactory;
        $this->listLanguagesParser = $listLanguagesParser;
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
            "--color='always'",
            "--paging='never'",
            "--style='plain'",
            '--theme="${internalTheme}"',
            '--language="${internalLanguage}"',
        ]);

        $process = $this->processFactory->fromShellCommandline($command);
        $exitCode = $process->run(
            null,
            [
                'code' => $code,
                'internalTheme' => $internalTheme,
                'internalLanguage' => $internalLanguage,
                'internalOutputFormat' => $internalOutputFormat,
            ],
        );
        if ($exitCode !== 0) {
            return $code;
        }

        return $process->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    protected function getInternalLanguages(): array
    {
        if ($this->internalLanguages === null) {
            $command = array_merge(
                $this->executable,
                [
                    '--list-languages',
                ],
            );

            $process = $this->processFactory->fromShellCommandline(implode(' ', $command));
            $exitCode = $process->run(null, $this->envVars);
            $result = $this->listLanguagesParser->parse(
                $exitCode,
                $process->getOutput(),
                $process->getErrorOutput()
            );

            $this->internalLanguages = $result['assets']['languages'] ?? [];
        }

        return $this->internalLanguages;
    }
}
