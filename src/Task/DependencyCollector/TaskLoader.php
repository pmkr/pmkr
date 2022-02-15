<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait TaskLoader
{
    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\DependencyCollector\PackagesFromInstanceTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrCollectPackageDependenciesFromInstance(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\DependencyCollector\PackagesFromInstanceTask $task */
        $task = $this->task(
            PackagesFromInstanceTask::class,
            $container->get('pmkr.utils'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\DependencyCollector\PackagesFromExtensionsTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrCollectPackageDependenciesFromExtensions(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\DependencyCollector\PackagesFromExtensionsTask $task */
        $task = $this->task(
            PackagesFromExtensionsTask::class,
            $container->get('pmkr.utils'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\DependencyCollector\LibrariesFromInstanceTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrCollectLibraryDependenciesFromInstance(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            LibrariesFromInstanceTask::class,
            $container->get('pmkr.utils'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\DependencyCollector\LibrariesFromExtensionTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrCollectLibraryDependenciesFromExtension(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\DependencyCollector\LibrariesFromExtensionTask $task */
        $task = $this->task(
            LibrariesFromExtensionTask::class,
            $container->get('pmkr.utils'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\DependencyCollector\LibrariesFromExtensionsTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrCollectLibraryDependenciesFromExtensions(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\DependencyCollector\LibrariesFromExtensionsTask $task */
        $task = $this->task(
            LibrariesFromExtensionsTask::class,
            $container->get('pmkr.utils'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @see \League\Container\ContainerAwareInterface::getContainer()
     */
    abstract public function getContainer() : ContainerInterface;
}
