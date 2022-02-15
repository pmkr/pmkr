<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\Task\Instance\InstanceTaskLoader;
use Robo\Contract\TaskInterface;

class InstanceDeleteCommand extends CommandBase
{

    use InstanceTaskLoader;

    /**
     * @command instance:delete
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName arg.instanceName
     * @pmkrValidateInstanceName
     */
    public function cmdInstanceDeleteExecute(
        string $instanceName
    ): TaskInterface {
        $instance = $this->getPmkr()->instances[$instanceName];

        return $this
            ->taskPmkrInstanceDelete()
            ->setInstance($instance);
    }
}
