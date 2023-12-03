<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

use Pmkr\Pmkr\PackageManager\Apt\ListParser;
use Symfony\Component\Process\Process;

class Apt extends HandlerBase
{

    protected ListParser $listParser;

    public function __construct(ListParser $listParser)
    {
        $this->listParser = $listParser;
    }

    /**
     * @param array<string> $packageNames
     */
    public function missingCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return '';
        }

        $cmdPattern = 'apt -qq list';
        $cmdArgs = [];
        $cmdPattern .= str_repeat(' %s', count($packageNames));
        foreach ($packageNames as $packageName) {
            $cmdArgs[] = escapeshellarg($packageName);
        }

        $cmdPattern .= ' 2>/dev/null | cat';

        return vsprintf($cmdPattern, $cmdArgs);
    }

    /**
     * {@inheritdoc}
     */
    public function missing(array $packageNames): array
    {
        $command = $this->missingCommand($packageNames);
        if ($command === '') {
            return [
                'messages' => [],
                'installed' => [],
                'not-installed' => [],
                'missing' => [],
            ];
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

        return $this->listParser->parseMissing(
            $packageNames,
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
        );
    }

    /**
     * @param array<string> $packageNames
     */
    public function installCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return 'true';
        }

        $cmdPattern = 'apt-get install -y';
        $cmdArgs = [];
        $cmdPattern .= str_repeat(' %s', count($packageNames));
        foreach ($packageNames as $packageName) {
            $cmdArgs[] = escapeshellarg($packageName);
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    /**
     * @param array<string> $packageNames
     */
    public function install(array $packageNames): static
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
        return 'apt-get update -y';
    }
}
