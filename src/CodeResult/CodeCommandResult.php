<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\CodeResult;

use Consolidation\AnnotatedCommand\CommandResult;

/**
 * @method static static data(null|\Pmkr\Pmkr\CodeResult\CodeResult $data)
 * @method static static dataWithExitCode(null|\Pmkr\Pmkr\CodeResult\CodeResult $data, int $exitCode)
 *
 * @method $this setOutputData(null|\Pmkr\Pmkr\CodeResult\CodeResult $data)
 * @method null|\Pmkr\Pmkr\CodeResult\CodeResult getOutputData()
 */
class CodeCommandResult extends CommandResult
{

    /**
     * @param null|\Pmkr\Pmkr\CodeResult\CodeResult $data
     * @param int $exitCode
     */
    public function __construct($data = null, $exitCode = 0)
    {
        parent::__construct($data, $exitCode);
    }
}
