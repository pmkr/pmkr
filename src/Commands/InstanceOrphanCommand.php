<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboState;
use Pmkr\Pmkr\TaskOverride\Filesystem\DeleteDirTaskLoader;
use Pmkr\Pmkr\Util\InstanceCollector;

class InstanceOrphanCommand extends CommandBase
{

    use DeleteDirTaskLoader;

    protected InstanceCollector $instanceCollector;

    /**
     * @return $this
     */
    protected function initDependencies()
    {
        if (!$this->initialized) {
            parent::initDependencies();
            $container = $this->getContainer();
            $this->instanceCollector = $container->get('pmkr.instance.collector');
        }

        return $this;
    }

    /**
     * Lists all instances from ${dir.src} and ${dir.share} directories which
     * has no corresponding definition in any pmkr.*.yml files.
     *
     * @command instance:orphan:list
     */
    public function cmdInstanceOrphanListExecute(): CommandResult
    {
        return CommandResult::data(
            $this
                ->instanceCollector
                ->collectOrphans($this->getConfig())
        );
    }

    /**
     * Deletes all directories which are belong to an orphan instance.
     *
     * Orphan instances can be check with the `pmkr instance:orphan:list`
     * command.
     *
     * @command instance:orphan:delete
     */
    public function cmdInstanceOrphanDeleteExecute(): TaskInterface
    {
        return $this
            ->collectionBuilder()
            ->addCode(function (RoboState $state): int {
                $orphans = $this->instanceCollector->collectOrphans($this->getConfig());
                $state['orphanDirs'] = $this->instanceCollector->flattenOrphanDirs($orphans);

                return 0;
            })
            ->addTask(
                $this
                    ->taskDeleteDir()
                    ->deferTaskConfiguration('setDirs', 'orphanDirs')
            );
    }
}
