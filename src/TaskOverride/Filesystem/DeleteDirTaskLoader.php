<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\TaskOverride\Filesystem;

use Robo\Collection\CollectionBuilder;

trait DeleteDirTaskLoader
{

    /**
     * @return \Pmkr\Pmkr\TaskOverride\Filesystem\DeleteDirTask|\Robo\Collection\CollectionBuilder
     *
     * @link https://github.com/consolidation/robo/issues/1078
     */
    protected function taskDeleteDir(iterable $dirs = []): CollectionBuilder
    {
        return $this->task(DeleteDirTask::class, $dirs);
    }

    /**
     * @param string $className
     *   FQN.
     * @param mixed ...$args
     *   Task constructor arguments.
     *
     * @return \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface
     *
     * @see \Robo\TaskAccessor::task()
     */
    abstract protected function task();
}
