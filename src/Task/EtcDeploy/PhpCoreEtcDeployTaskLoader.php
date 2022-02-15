<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\EtcDeploy;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait PhpCoreEtcDeployTaskLoader
{

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\EtcDeploy\PhpCoreEtcDeployTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpCoreEtcDeploy(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\EtcDeploy\PhpCoreEtcDeployTask $task */
        $task = $this->task(
            PhpCoreEtcDeployTask::class,
            $container->get('twig.environment'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    abstract public function getContainer() : ContainerInterface;
}
