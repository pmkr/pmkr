<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task;

use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait ConfigNormalizerTaskLoader
{
    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\ConfigNormalizerTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrConfigNormalizer(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            ConfigNormalizerTask::class,
            $container->get('pmkr.config.normalizer'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @see \League\Container\ContainerAwareInterface::getContainer()
     */
    abstract public function getContainer() : DefinitionContainerInterface;
}
