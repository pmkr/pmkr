<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionDownload;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait TaskLoader
{

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\PhpExtensionDownload\WrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionDownloadWrapper(array $options = []): CollectionBuilder
    {
        /** @var \Pmkr\Pmkr\Task\PhpExtensionDownload\WrapperTask $task */
        $task = $this->task(WrapperTask::class);
        $task->setOptions($options);
        $task->setContainer($this->getContainer());

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\PhpExtensionDownload\PeclTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionDownloadPecl(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\PhpExtensionDownload\PeclTask $task */
        $task = $this->task(
            PeclTask::class,
            $container->get('pmkr.php_extension.version_detector'),
            $container->get('pecl.client'),
            $container->get('pmkr.utils'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\PhpExtensionDownload\GitTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionDownloadGit(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\PhpExtensionDownload\GitTask $task */
        $task = $this->task(
            GitTask::class,
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
