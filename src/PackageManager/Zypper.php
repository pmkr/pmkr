<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

use Pmkr\Pmkr\PackageManager\Zypper\SearchParser;
use Symfony\Component\Process\Process;

class Zypper extends HandlerBase
{

    protected SearchParser $searchParser;

    public function __construct(SearchParser $searchParser)
    {
        $this->searchParser = $searchParser;
    }

    public function missingCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return '';
        }

        $config = $this->getConfig();
        $executable = $config['executable'] ?? 'zypper';

        $cmdPattern = '%s --xmlout search --match-exact';
        $cmdArgs = [
            escapeshellcmd($executable),
        ];
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
        $validExitCodes = [0, SearchParser::EXIT_CODE_MISSING];
        if (!in_array($process->getExitCode(), $validExitCodes)) {
            return [];
        }

        // @todo Add to services.yml.
        $parser = new SearchParser();

        return $parser->parseMissing(
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

        $executable = 'zypper';
        $cmdPattern = '%s install -y';
        $cmdArgs = [escapeshellcmd($executable)];
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
        $config = $this->getConfig();
        $executable = $config['executable'] ?? 'zypper';

        return sprintf('% refresh', escapeshellcmd($executable));
    }
}
