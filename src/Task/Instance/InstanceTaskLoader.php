<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Instance;

use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait InstanceTaskLoader
{
    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Instance\DeleteTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrInstanceDelete(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\Instance\DeleteTask $task */
        $task = $this->task(
            DeleteTask::class,
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @see \League\Container\ContainerAwareInterface::getContainer()
     */
    abstract public function getContainer() : DefinitionContainerInterface;
}
