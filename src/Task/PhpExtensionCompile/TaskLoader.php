<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionCompile;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait TaskLoader
{

    /**
     * @return \Pmkr\Pmkr\Task\PhpExtensionCompile\BeforeConfigureTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrBeforeExtensionConfigure(array $options = []): CollectionBuilder
    {
        $task = $this->task(
            BeforeConfigureTask::class,
        );
        $task->setOptions($options);

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\PhpExtensionCompile\WrapperTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionCompileWrapper(array $options = []): CollectionBuilder
    {
        $task = $this->task(WrapperTask::class);
        $task->setOptions($options);
        $task->setContainer($this->getContainer());

        return $task;
    }

    /**
     * @return \Pmkr\Pmkr\Task\PhpExtensionCompile\PeclTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionCompilePecl(
        array $options = []
    ): CollectionBuilder {
        $container = $this->getContainer();
        $task = $this->task(
            PeclTask::class,
            $container->get('pmkr.php_extension.compile_configure_command.builder'),
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
