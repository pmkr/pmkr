<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Dnf;

use Pmkr\Pmkr\Utils;

class ListParser
{
    protected Utils $utils;

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * @return array{messages: string[], installed: string[], not-installed: string[], missing: string[]}
     */
    public function parseMissing(
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): array {
        $assets = $this->parseStdOutput($stdOutput);
        $assets += [
            'messages' => [],
            'installed' => [],
            'not-installed' => [],
        ];

        $assets['missing'] = array_diff(
            $packageNames,
            array_keys($assets['installed']),
            array_keys($assets['not-installed']),
        );

        return $assets;
    }

    protected function parseStdOutput(string $stdOutput): array
    {
        $lines = $this->utils->splitLines($stdOutput);
        $headers = [
            'Installed Packages' => 'installed',
            'Available Packages' => 'not-installed',
        ];
        $packages = [];
        $status = 'unknown';
        $packageNames = [];
        foreach ($lines as $line) {
            if (array_key_exists($line, $headers)) {
                $status = $headers[$line];

                continue;
            }

            $package = $this->parseStdOutputLine($line);
            $package['status'] = $status;

            // libxml2.x86_64
            // libxml2.i686
            if (isset($packageNames[$package['name']])) {
                continue;
            }

            $packageNames[$package['name']] = true;
            $packages[$status][$package['name']] = $package;
        }

        return $packages;
    }

    protected function parseStdOutputLine(string $line): array
    {
        $parts = preg_split('/\s+/', $line);
        $values = [];

        $values['version'] = $parts[1] ?? null;
        $parts = explode('.', $parts[0]);
        $values['architecture'] = count($parts) > 1 ? array_pop($parts) : null;
        $values['name'] = implode('.', $parts);

        return $values;
    }
}
