<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Patch;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait TaskLoader
{
    /**
     * @return \Pmkr\Pmkr\Task\Patch\ApplyPatchTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskBar(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            ApplyPatchTask::class,
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @see \League\Container\ContainerAwareInterface::getContainer()
     */
    abstract public function getContainer() : ContainerInterface;
}
