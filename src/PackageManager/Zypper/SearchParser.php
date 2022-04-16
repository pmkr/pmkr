<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Zypper;

class SearchParser
{

    const EXIT_CODE_MISSING = 104;

    /**
     * @param string[] $packageNames
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
            'missing' => [],
        ];

        if (!$stdOutput) {
            return $assets;
        }

        $xml = new \DOMDocument();
        $xml->loadXML($stdOutput);

        /** @var \DOMElement $solvable */
        foreach ($xml->getElementsByTagName('solvable') as $solvable) {
            $package = [
                'name' => $solvable->getAttribute('name'),
                'status' => $solvable->getAttribute('status'),
                'summary' => $solvable->getAttribute('summary'),
                'kind' => $solvable->getAttribute('kind'),
            ];

            if ($package['kind'] !== 'package') {
                // The same package name can exists multiple times with
                // different kind. Such as "package" or "srcpackage".
                continue;
            }

            switch ($package['status']) {
                case 'installed':
                    $assets['installed'][$package['name']] = $package;
                    break;
                case 'not-installed':
                    $assets['not-installed'][$package['name']] = $package;
                    break;
            }
        }

        $assets['missing'] = array_diff(
            $packageNames,
            array_keys($assets['installed']),
            array_keys($assets['not-installed']),
        );

        return $assets;
    }
}
