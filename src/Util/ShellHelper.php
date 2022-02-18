<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

class ShellHelper
{

    protected ProcessFactory $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * @return array{
     *     PHPRC?: string,
     *     PHP_INI_SCAN_DIR?: string,
     * }
     */
    public function collectPhpIniPaths(string $phpBinary): array
    {
        $paths = [];

        $command = sprintf(
            '( unset PHPRC PHP_INI_SCAN_DIR ; LANGUAGE=%s %s -i )',
            escapeshellarg('en_GB:en_US'),
            escapeshellarg($phpBinary),
        );

        $process = $this->processFactory->createInstance(['bash', '-c', $command]);
        $process->run();
        $stdOutput = $process->getOutput();
        $matches = [];
        preg_match(
            '/(?<=^Loaded Configuration File => )(?P<value>.+)\n/um',
            $stdOutput,
            $matches,
        );

        if (!empty($matches['value'])) {
            $paths['PHPRC'] = $matches['value'];
        }

        $matches = [];
        preg_match(
            '/(?<=^Scan this dir for additional .ini files => )(?P<value>.+)\n/um',
            $stdOutput,
            $matches,
        );
        if (!empty($matches['value'])) {
            $paths['PHP_INI_SCAN_DIR'] = $matches['value'];
        }

        return $paths;
    }
}
