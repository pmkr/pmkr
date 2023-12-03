<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\ProcessResultParser;

use Pmkr\Pmkr\Utils;

class BatListLanguagesParser extends ParserBase
{
    protected Utils $utils;

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * @return array{
     *     exitCode: int,
     *     assets: array{
     *         languages?: array<
     *             string,
     *             array{
     *                 patterns?: array<string>,
     *             },
     *         >
     *     },
     * }
     */
    public function parse(
        int $exitCode,
        string $stdOutput,
        string $stdError,
    ): array {
        $return = [
            'exitCode' => $exitCode,
            'assets' => [],
        ];

        if ($exitCode !== 0) {
            return $return;
        }

        $assetName = $this->getExternalAssetName('languages');
        $return['assets'][$assetName] = [];
        foreach ($this->utils->splitLines(trim($stdOutput)) as $line) {
            [$language, $patterns] = explode(':', $line, 2);
            $return['assets'][$assetName][$language] = [
                'patterns' => explode(',', $patterns),
            ];
        }

        return $return;
    }
}
