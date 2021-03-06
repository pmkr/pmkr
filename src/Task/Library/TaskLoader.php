<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait TaskLoader
{

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\DownloaderWrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadWrapper(array $options = []): CollectionBuilder
    {
        /** @var \Pmkr\Pmkr\Task\Library\DownloaderWrapperTask $task */
        $task = $this->task(DownloaderWrapperTask::class);
        $task->setOptions($options);
        $task->setContainer($this->getContainer());

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\ArchiveDownloaderTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadArchive(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\Library\ArchiveDownloaderTask $task */
        $task = $this->task(
            ArchiveDownloaderTask::class,
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\GitDownloaderTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadGit(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\Library\GitDownloaderTask $task */
        $task = $this->task(
            GitDownloaderTask::class,
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\CompilerWrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryCompilerWrapper(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\Library\CompilerWrapperTask $task */
        $task = $this->task(
            CompilerWrapperTask::class,
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\CommonCompilerTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryCompilerCommon(array $options = []): CollectionBuilder
    {
        /** @var \Pmkr\Pmkr\Task\Library\CommonCompilerTask $task */
        $task = $this->task(CommonCompilerTask::class);
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\Library\InstallTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryInstall(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\Library\InstallTask $task */
        $task = $this->task(InstallTask::class);
        $task->setContainer($container);
        $task->setOptions($options);

        return $task;
    }

    /**
     * @see \League\Container\ContainerAwareInterface::getContainer()
     */
    abstract public function getContainer() : DefinitionContainerInterface;
}
