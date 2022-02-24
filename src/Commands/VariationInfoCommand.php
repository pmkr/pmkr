<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\VariationPickResult\VariationPickResult;

class VariationInfoCommand extends CommandBase
{
    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @command variation:info
     *
     * @aliases vi
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractVariationKey arg.variationKey
     * @pmkrValidateVariationKey arg.variationKey
     */
    public function cmdVariationInfoExecute(
        string $variationKey = '',
        array $options = [
            'format' => 'string',
        ]
    ): ?VariationPickResult {
        $pmkr = $this->getPmkr();
        /** @var \Pmkr\Pmkr\Model\Variation $variation */
        $variation = $pmkr->variations[$variationKey];

        $result = new VariationPickResult();
        $result->key = $variation->key;
        $result->instance = $variation->instance;
        $result->phpRc = $variation->phpRc;
        $result->phpIniScanDir = $variation->phpIniScanDir;

        return $result;
    }
}
