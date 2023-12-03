<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Apk;

use Pmkr\Pmkr\Utils;

class ListParser
{
    protected Utils $utils;

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * @param array<string> $packageNames
     *
     * @return array{
     *     messages?: array<string>,
     *     missing?: array<string>,
     *     installed: array<string, ApkListItem>,
     *     not-installed: array<string, ApkListItem>,
     * }
     */
    public function parseMissing(
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError,
    ): array {
        $assets = [
            'messages' => [],
            'installed' => [],
            'not-installed' => [],
        ];
        $lines = $this->utils->splitLines($stdOutput);
        foreach ($lines as $line) {
            $package = $this->parseMissingLine($line);
            $status = in_array('installed', $package['status']) ? 'installed' : 'not-installed';
            $assets[$status][$package['name']] = $package;
        }

        $assets['missing'] = array_diff(
            $packageNames,
            array_keys($assets['installed']),
            array_keys($assets['not-installed']),
        );

        return $assets;
    }

    /**
     * @return ApkListItem
     */
    protected function parseMissingLine(string $line): array
    {
        $parts = explode(' ', $line, 4);
        $info = [
            'id' => $parts[0],
            'architecture' => $parts[1],
            'name' => trim($parts[2], '{}'),
        ];


        $matches = [];
        if (preg_match('/^(?P<name>.+?)-\d+\.\d+/', $line, $matches) === 1) {
            $info['name'] = $matches['name'];
        }

        $parts2 = explode(') [', $parts[3], 2) + [1 => ''];
        $parts[3] = trim($parts2[0], '()');
        $parts[4] = trim($parts2[1], '[]');

        $info['version'] = mb_substr($info['id'], mb_strlen($info['name']) + 1);
        $info['license'] = $parts[3] ? explode(' ', $parts[3]) : [];
        $info['status'] = $parts[4] ? explode(',', $parts[4]) : ['not-installed'];

        return $info;
    }
}
