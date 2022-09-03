<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;

class OsCommand extends CommandBase
{

    /**
     * @command os:info
     *
     * @option string $format
     *   Default: yaml
     */
    public function cmdOsInfoExecute(): CommandResult
    {
        $opSys = $this->getContainer()->get('pmkr.op_sys');

        $data = [
            'id' => $opSys->id(),
            'id_like' => $opSys->idLike(),
            'version_id' => $opSys->versionId(),
            'packageManager' => $opSys->packageManager(),
            'family' => $opSys->family(),
        ];

        return CommandResult::data($data);
    }
}
