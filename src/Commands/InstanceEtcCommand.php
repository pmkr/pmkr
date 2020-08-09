<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\Task\EtcDeploy\PhpCoreEtcDeployTaskLoader;
use Pmkr\Pmkr\Task\EtcDeploy\PhpExtensionEtcDeployTaskLoader;
use Pmkr\Pmkr\Util\PhpExtensionVersionDetector;
use Robo\Collection\CollectionBuilder;

class InstanceEtcCommand extends CommandBase
{
    use PhpCoreEtcDeployTaskLoader;
    use PhpExtensionEtcDeployTaskLoader;

    protected PhpExtensionVersionDetector $extensionVersionDetector;

    protected function initDependencies()
    {
        if (!$this->initialized) {
            parent::initDependencies();
            $container = $this->getContainer();
            $this->extensionVersionDetector = $container->get('pmkr.php_extension.version_detector');
        }

        return $this;
    }

    /**
     * Deploys all the *.ini files into the "instanceShareDir/etc" directory.
     *
     * @command instance:etc:deploy
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInitCurrentInstanceName arg.instanceName
     * @pmkrInteractInstanceName
     *   arg.instanceName:
     *      hasShareDir: true
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstance
     *   arg.instanceName:
     *      hasShareDir: true
     */
    public function cmdInstanceEtcDeployExecute(string $instanceName)
    {
        $pmkr = $this->getPmkr();
        $instance = $pmkr->instances[$instanceName];

        $cb = $this->collectionBuilder();
        $cb->addTask(
            $this
                ->taskPmkrPhpCoreEtcDeploy()
                ->setInstance($instance)
        );

        $extensionSet = iterator_to_array($instance->extensionSet->getIterator());
        $extensionsAll = iterator_to_array($pmkr->extensions->getIterator());
        $extensions = array_intersect_key($extensionsAll, $extensionSet);
        $taskForEach = $this->taskForEach($extensions);
        $taskForEach
            ->iterationMessage('Deploy etc files for extension: {key}')
            ->withBuilder(function (CollectionBuilder $builder, $extensionKey, $extension) use ($instance) {
                $builder->addTask(
                    $this
                        ->taskPmkrPhpExtensionEtcDeploy()
                        ->setInstance($instance)
                        ->setExtension($extension)
                );
            });

        $cb->addTask($taskForEach);

        return $cb;
    }
}
