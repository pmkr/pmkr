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

    /**
     * @param array<string> $packageNames
     */
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
     * @param array<string> $packageNames
     *
     * @return array{
     *   messages: string[],
     *   installed: array<string, array{
     *     name: string,
     *     status: string,
     *     summary: string,
     *     kind: string,
     *   }>,
     *   not-installed: array<string, array{
     *     name: string,
     *     status: string,
     *     summary: string,
     *     kind: string,
     *   }>,
     *   missing: string[],
     * }
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
        $validExitCodes = [0, SearchParser::EXIT_CODE_MISSING];
        if (!in_array($process->getExitCode(), $validExitCodes)) {
            return [
                'messages' => [],
                'installed' => [],
                'not-installed' => [],
                'missing' => [],
            ];
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

    /**
     * @param array<string> $packageNames
     *
     * @return string
     */
    public function installCommand(array $packageNames): string
    {
        if (!$packageNames) {
            return 'true';
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

    /**
     * @param string[] $packageNames
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
        $config = $this->getConfig();
        $executable = $config['executable'] ?? 'zypper';

        return sprintf('%s refresh', escapeshellcmd($executable));
    }
}
