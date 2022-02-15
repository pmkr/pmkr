<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\EtcDeploy;

use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;

/**
 * @method \Robo\Collection\CollectionBuilder|\Robo\Contract\TaskInterface task(string $className, ...$args)
 */
trait PhpExtensionEtcDeployTaskLoader
{

    /**
     * @param array<string, mixed> $options
     *
     * @return \Pmkr\Pmkr\Task\EtcDeploy\PhpExtensionEtcDeployTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPmkrPhpExtensionEtcDeploy(array $options = []): CollectionBuilder
    {
        $container = $this->getContainer();
        /** @var \Pmkr\Pmkr\Task\EtcDeploy\PhpExtensionEtcDeployTask $task */
        $task = $this->task(
            PhpExtensionEtcDeployTask::class,
            $container->get('pmkr.php_extension.version_detector'),
            $container->get('twig.environment'),
            $container->get('filesystem'),
        );
        $task->setOptions($options);

        return $task;
    }

    abstract public function getContainer() : ContainerInterface;
}
