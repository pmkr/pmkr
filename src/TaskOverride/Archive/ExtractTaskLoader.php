<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\TaskOverride\Archive;

use Robo\Collection\CollectionBuilder;

trait ExtractTaskLoader
{

    /**
     * @return \Pmkr\Pmkr\TaskOverride\Archive\ExtractTask|\Robo\Collection\CollectionBuilder
     *
     * @link https://github.com/consolidation/robo/issues/1078
     */
    protected function taskExtract(string $fileName = ''): CollectionBuilder
    {
        return $this->task(ExtractTask::class, $fileName);
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
