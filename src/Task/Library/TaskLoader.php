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
     * @return \Pmkr\Pmkr\Task\Library\DownloaderWrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadWrapper(array $options = []): CollectionBuilder
    {
        $task = $this->task(DownloaderWrapperTask::class);
        $task->setOptions($options);
        $task->setContainer($this->getContainer());

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\Library\ArchiveDownloaderTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadArchive(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            ArchiveDownloaderTask::class,
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\Library\GitDownloaderTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryDownloadGit(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            GitDownloaderTask::class,
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\Library\CompilerWrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryCompilerWrapper(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        $task = $this->task(
            CompilerWrapperTask::class,
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\Library\CommonCompilerTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryCompilerCommon(array $options = []): CollectionBuilder
    {
        $task = $this->task(CommonCompilerTask::class);
        $task->setOptions($options);

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\Library\InstallTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrLibraryInstall(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
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
