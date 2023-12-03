<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\ProcessResultParser;

class TerminalColorParser extends ParserBase
{
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

        // Example: 11;rgb:2c2c/2c2c/2c2c
        $matches = [];
        preg_match(
            '@^(?P<color>\d+);rgb:(?P<r>[0-9a-f]{4})/(?P<g>[0-9a-f]{4})/(?P<b>[0-9a-f]{4})$@',
            trim($stdOutput),
            $matches,
        );
        if (!$matches) {
            return $return;
        }

        $return['assets'][$this->getExternalAssetName('color')] = $matches['color'];

        $return['assets'][$this->getExternalAssetName('rgb_16')] = [
            'r' => $matches['r'],
            'g' => $matches['g'],
            'b' => $matches['b'],
        ];

        $return['assets'][$this->getExternalAssetName('rgb_10')] = [
            'r' => base_convert($matches['r'], 16, 10),
            'g' => base_convert($matches['g'], 16, 10),
            'b' => base_convert($matches['b'], 16, 10),
        ];

        return $return;
    }
}
