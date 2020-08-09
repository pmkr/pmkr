<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

class VariationListCommand extends CommandBase
{

    /**
     * @command variation:list
     */
    public function cmdVariationListExecute(
        array $options = []
    ) {
        $app = $this->getContainer()->get('application');
        $appName = $app->getName();
        $this->logger->warning(implode(\PHP_EOL, [
            'Sorry - Not implemented yet.',
            'Until then, one of the following command will do the trick:',
            "$appName config:export --format='json' | jq '.variations'",
            "$appName config:export --format='yaml' | yq eval '.variations' -",
        ]));
    }
}
