<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Pacman;

use Pmkr\Pmkr\Utils;
use Symfony\Component\Yaml\Yaml;

class QueryParser
{
    protected Utils $utils;

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    public function parseMissing(
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): array {
        $assets = [
            'missing' => [],
            'installed' => [],
        ];

        $parts = explode("\n\n", trim($stdOutput));
        foreach ($parts as $part) {
            if (!$part) {
                continue;
            }

            $info = [];
            $lines = $this->utils->splitLines($part);
            foreach ($lines as $line) {
                if (preg_match('/^\s/', $line) === 1) {
                    continue;
                }

                [$key, $value] = preg_split('/\s+:\s+/', $line, 2);
                $info[$key] = $value;
            }

            $assets['installed'][$info['Name']] = $info;
        }

        $pattern = "/package '(?P<name>[^']+)' was not found/";
        $matches = [];
        preg_match_all($pattern, $stdError, $matches);
        $assets['not-installed'] = array_fill_keys($matches['name'] ?? [], []);

        return $assets;
    }
}
