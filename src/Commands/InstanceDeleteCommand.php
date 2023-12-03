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
     * @pmkrInteractInstanceName
     *     arg.instanceName:
     *         hasShareDir: true
     * @pmkrValidateInstanceName arg.instanceName
     */
    public function cmdInstanceDeleteExecute(string $instanceName): TaskInterface
    {
        // NOTE: Instance name alias is intentionally not resolved.
        $instance = $this->getPmkr()->instances[$instanceName];

        return $this
            ->taskPmkrInstanceDelete()
            ->setInstance($instance);
    }
}
