<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\VariationPickResult\VariationPickResult;
use Symfony\Component\Console\Input\InputOption;

class VariationPickCommand extends CommandBase
{

    /**
     * @command variation:pick
     *
     * @aliases vp
     *
     * @pmkrInitNormalizeConfig
     * @pmkrValidateVariationKey arg.variationKey
     */
    public function cmdVariationPickExecute(
        $variationKey,
        array $options = [
            'binary' => InputOption::VALUE_REQUIRED,
            'format' => 'shell-var-setter',
        ]
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
