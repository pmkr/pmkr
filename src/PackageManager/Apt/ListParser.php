<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Apt;

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
     *     installed: array<string, array{
     *         name?: string,
     *         type?: string,
     *         architecture?: ?string,
     *         status?: array<string>,
     *         version?: ?string,
     *     }>,
     *     not-installed: array<string, array{
     *         name?: string,
     *         type?: string,
     *         architecture?: ?string,
     *         status?: array<string>,
     *         version?: ?string,
     *     }>,
     * }
     */
    public function parseMissing(
        array $packageNames,
        int $exitCode,
        string $stdOutput,
        string $stdError
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
     * @return array{
     *   name: string,
     *   type: string,
     *   version: string,
     *   architecture: string,
     *   status: array<string>,
     * }
     */
    protected function parseMissingLine(string $line): array
    {
        $values = (array) array_combine(
            [
                'package',
                'version',
                'architecture',
                'status',
            ],
            explode(' ', $line) + array_fill(0, 4, ''),
        );

        $parts = explode('/', $values['package'] ?: '/');
        $info = [];
        $info['name'] = $parts[0];
        $info['type'] = $parts[1];
        $info['version'] = $values['version'] ?: '';
        $info['architecture'] = $values['architecture'] ?: '';
        $info['status'] = $this->utils->explodeCommaSeparatedList(trim($values['status'] ?: '', '[]'));

        return $info;
    }
}
