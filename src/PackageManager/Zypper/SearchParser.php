<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager\Zypper;

class SearchParser
{

    const EXIT_CODE_MISSING = 104;

    /**
     * @return array{'messages': string[], 'installed': string[], 'not-installed': string[], 'missing': string[]}
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

            $assets[$package['status']][$package['name']] = $package;
        }

        $assets['missing'] = array_diff(
            $packageNames,
            array_keys($assets['installed']),
            array_keys($assets['not-installed']),
        );

        return $assets;
    }
}
