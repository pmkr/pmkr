<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\VariationPickResult\VariationPickResult;

class VariationPickCommand extends CommandBase
{

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @command variation:pick
     *
     * @aliases vp
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInitReadStdInput arg.variationKey
     * @pmkrValidateVariationKey arg.variationKey
     */
    public function cmdVariationPickExecute(
        string $variationKey,
        array $options = [
            'binary' => 'php',
            'format' => 'shell-var-setter',
        ],
    ): ?VariationPickResult {
        $pmkr = $this->getPmkr();
        $variation = $pmkr->variations[$variationKey];
        $aliases = $pmkr->aliases;
        $instanceKey = $variation->instanceKey;
        if (isset($aliases[$instanceKey])) {
            $instanceKey = $aliases[$instanceKey];
        }

        $result = new VariationPickResult();
        $result->instance = $pmkr->instances[$instanceKey];
        $result->phpRc = $variation->phpRc;
        $result->phpIniScanDir = $variation->phpIniScanDir;
        $result->binary = $options['binary'] ?: 'php';

        return $result;
    }
}
