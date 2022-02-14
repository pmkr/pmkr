<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\SyntaxHighlighter\Backend;

use Pmkr\Pmkr\Util\ProcessFactory;
use Pmkr\Pmkr\Utils;

class Yq extends Base
{
    protected array $executable = [
        'Yq',
    ];

    protected ProcessFactory $processFactory;

    /**
     * {@inheritdoc}
     */
    protected array $languageMapping = [
        'yaml' => 'yaml',
    ];

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
        $command = implode(' ', [
            'echo',
            '-n',
            '"${code}"',
            '|',
            implode(' ', $this->executable),
            'eval',
            '.',
            '-',
        ]);

        $process = $this->processFactory->fromShellCommandline($command);
        $exitCode = $process->run(
            null,
            [
                'code' => $code,
            ],
        );
        if ($exitCode !== 0) {
            return $code;
        }

        return $process->getOutput();
    }
}
