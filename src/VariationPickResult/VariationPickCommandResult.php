<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\VariationPickResult;

use Consolidation\AnnotatedCommand\CommandResult;

class VariationPickCommandResult extends CommandResult
{
    public function __construct($data = null, $exitCode = 0)
    {
        parent::__construct($data, $exitCode);
    }
}
