<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

use Pmkr\Pmkr\PackageManager\Pacman\QueryParser;
use Symfony\Component\Process\Process;

class Pacman extends HandlerBase
{

    protected QueryParser $queryParser;

    public function __construct(QueryParser $queryParser)
    {
        $this->queryParser = $queryParser;
    }

    public function missingCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return '';
        }

        $cmdPattern = 'pacman --query --info';
        $cmdArgs = [];
        $cmdPattern .= str_repeat(' %s', count($packageNames));
        foreach ($packageNames as $packageName) {
            $cmdArgs[] = escapeshellarg($packageName);
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    /**
     * @return array{messages: string[], installed: string[], not-installed: string[], missing: string[]}
     */
    public function missing(array $packageNames): array
    {
        $command = $this->missingCommand($packageNames);
        if ($command === '') {
            return [];
        }

        $callback = function ($type, $text) {
            // Do nothing.
        };
        $process = Process::fromShellCommandline(
            $command,
            null,
            null,
            null,
            180,
        );
        $process->run($callback, ['LANGUAGE' => 'en_GB:en_US']);

        return $this->queryParser->parseMissing(
            $packageNames,
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
        );
    }

    public function installCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return '';
        }

        $cmdPattern = 'pacman --noconfirm --sync';
        $cmdArgs = [];
        $cmdPattern .= str_repeat(' %s', count($packageNames));
        foreach ($packageNames as $packageName) {
            $cmdArgs[] = escapeshellarg($packageName);
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    public function install(array $packageNames)
    {
        $command = $this->installCommand($packageNames);
        if ($command === '') {
            return $this;
        }

        // @todo Error handler.
        $process = Process::fromShellCommandline(
            $command,
            null,
            null,
            null,
            null,
        );
        $process->run();

        return $this;
    }

    public function refreshCommand(): string
    {
        return 'pacman --sync --refresh';
    }
}
