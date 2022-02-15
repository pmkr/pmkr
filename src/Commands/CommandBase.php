<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Task\ConfigNormalizerTaskLoader;
use Pmkr\Pmkr\Task\DependencyCollector\TaskLoader as PackageDependencyCollectorTaskLoader;
use Pmkr\Pmkr\Task\EtcDeploy\PhpExtensionEtcDeployTaskLoader;
use Pmkr\Pmkr\Task\Library\TaskLoader as LibraryTaskLoader;
use Pmkr\Pmkr\Task\PhpExtensionCompile\TaskLoader as PhpExtensionCompileTaskLoader;
use Pmkr\Pmkr\Task\PhpExtensionDownload\TaskLoader as PhpExtensionDownloadTaskLoader;
use Pmkr\Pmkr\Util\Filter\PhpExtensionFilter;
use Pmkr\Pmkr\Utils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Collection\Tasks as LoopTaskLoader;
use Robo\Common\ConfigAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboState;
use Robo\Task\Base\Tasks as CommonTaskLoader;
use Robo\TaskAccessor;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CommandBase implements
    BuilderAwareInterface,
    ConfigAwareInterface,
    ContainerAwareInterface,
    IOAwareInterface,
    LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use ConfigNormalizerTaskLoader;
    use DownloadCurlTaskLoader;
    use PackageDependencyCollectorTaskLoader;
    use IO;
    use TaskAccessor;
    use CommonTaskLoader;
    use LoopTaskLoader;
    use LibraryTaskLoader;
    use PhpExtensionDownloadTaskLoader;
    use PhpExtensionCompileTaskLoader;
    use PhpExtensionEtcDeployTaskLoader;

    protected bool $initialized = false;

    protected Filesystem $fs;

    protected Utils $utils;

    protected ?PmkrConfig $pmkr = null;

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook init *
     */
    public function hookInitDependencies(
        InputInterface $input,
        AnnotationData $annotationData
    ): void {
        $this->initDependencies();
    }

    /**
     * @return $this
     */
    protected function initDependencies()
    {
        if (!$this->initialized) {
            $container = $this->getContainer();
            $this->utils = $container->get('pmkr.utils');
            $this->fs = $container->get('filesystem');

            $this->initialized = true;
        }

        return $this;
    }

    protected function getPmkr(): PmkrConfig
    {
        if ($this->pmkr === null) {
            $this->pmkr = PmkrConfig::__set_state([
                'config' => $this->getConfig(),
                'configPath' => [],
            ]);
        }

        return $this->pmkr;
    }

    protected function getTaskOsDetector(string $dstStateKey = 'opSys'): \Closure
    {
        return function (RoboState $state) use ($dstStateKey): int {
            $this->logger->notice('PMKR - Initialize operating system detector');
            $state[$dstStateKey] = $this->getContainer()->get('pmkr.op_sys');

            return 0;
        };
    }

    /**
     * @todo This one could merged into ::getTaskOsDetector.
     */
    protected function getTaskPackageManagerFactory(string $opSysStateKey, string $dstStateKey): \Closure
    {
        return function (RoboState $state) use ($opSysStateKey, $dstStateKey): int {
            $this->logger->notice('PMKR - Package manager factory');

            /** @var \Pmkr\Pmkr\OpSys\OpSys $opSys */
            $opSys = $state[$opSysStateKey];
            $config = $this->getConfig();

            $pmFactory = $this->getContainer()->get('pmkr.package_manager.factory');
            $handlerName = $opSys->packageManager();
            if ($handlerName === null) {
                $this->logger->error('package manager handler could not be detected');

                return 1;
            }

            $handlerConfig = (array) $config->get("packageManagers.$handlerName.config");
            $handler = $pmFactory->createInstance($handlerName, $handlerConfig);
            if (!$handler) {
                $this->logger->error(
                    'package manager handler {handler} could not be created',
                    [
                        'handler' => $handlerName,
                    ],
                );

                return 1;
            }

            $state[$dstStateKey] = $handler;

            return 0;
        };
    }

    protected function getTaskPhpCoreInstallInit(string $instanceName): \Closure
    {
        return function (RoboState $state) use ($instanceName): int {
            $this->logger->notice('PMKR - Initialize task pipeline state values.');

            $instance = $this->getPmkr()->instances[$instanceName];

            $state['instance'] = $instance;
            $state['instanceSrcDir'] = $state['instance']->srcDir;

            $extensionFilter = new PhpExtensionFilter();
            $extensionFilter
                ->setExtensionSet($instance->extensionSet)
                ->setIgnore(['never', $instance->threadType]);
            $state['extensions'] = array_filter($state['instance']->extensions, $extensionFilter);

            $extensionComparer = $this->getContainer()->get('array_value.comparer');
            $extensionComparer->setKeys([
                'weight' => 50,
                'name' => '',
            ]);
            uasort($state['extensions'], $extensionComparer);

            $extensionFilter->setStatus(['enabled']);
            $state['enabledExtensions'] = array_filter($state['instance']->extensions, $extensionFilter);

            $extensionFilter->setStatus(['optional']);
            $state['optionalExtensions'] = array_filter($state['instance']->extensions, $extensionFilter);

            return 0;
        };
    }

    protected function getTaskCollectPackageDependenciesFromInstance(
        string $instanceStateKey,
        string $opSysStateKey
    ): TaskInterface {
        return $this
            ->taskPmkrCollectPackageDependenciesFromInstance()
            ->deferTaskConfiguration('setOpSys', $opSysStateKey)
            ->deferTaskConfiguration('setInstance', $instanceStateKey);
    }

    protected function getTaskExtensionsPackageDependencyCollector(
        string $extensionsStateKey,
        string $opSysStateKey
    ): TaskInterface {
        return $this
            ->taskPmkrCollectPackageDependenciesFromExtensions()
            ->deferTaskConfiguration('setExtensions', $extensionsStateKey)
            ->deferTaskConfiguration('setOpSys', $opSysStateKey);
    }

    protected function getTaskCollectMissingPackageDependencies(
        string $pmStateKey,
        string $packageNamesStateKey,
        string $dstStateKeyPrefix
    ): \Closure {
        return function (
            RoboState $state
        ) use (
            $pmStateKey,
            $packageNamesStateKey,
            $dstStateKeyPrefix
        ): int {
            $this->logger->notice('PMKR - Collect missing package dependencies');

            $logger = $this->logger;

            /** @var \Pmkr\Pmkr\PackageManager\HandlerInterface $pm */
            $pm = $state[$pmStateKey];

            /** @var array<string> $packageNames */
            $packageNames = array_keys($state[$packageNamesStateKey]);

            if (!$packageNames) {
                $logger->notice('there are no required packages.');

                return 0;
            }

            $logger->warning('if this hang repository information has to be updated');
            $result = $pm->missing($packageNames);
            foreach ($result as $key => $asset) {
                $state["$dstStateKeyPrefix.$key"] = $asset;
            }

            $key = "$dstStateKeyPrefix.not-installed";
            if (!empty($state[$key])) {
                /** @var array<string> $missingPackages */
                $missingPackages = array_keys($state[$key]);
                $state["$key.installCommand"] = $pm->installCommand($missingPackages);
            }

            return 0;
        };
    }

    /**
     * Finds the missing packages and throws an error if there is any.
     *
     * @return \Closure
     */
    protected function getTaskCheckMissingPackageDependencies(
        string $checkResultStateKeyPrefix = 'packageManager.checkResult'
    ): \Closure {
        return function (RoboState $state) use ($checkResultStateKeyPrefix): int {
            $this->logger->notice('PMKR - Check that if there is any missing package');

            $key = "$checkResultStateKeyPrefix.missing";

            $exitCode = 0;
            if (!empty($state[$key])) {
                $exitCode = 1;
                $this->logger->error(
                    "{count} package is missing:\n{packageNames}",
                    [
                        'count' => count($state[$key]),
                        'packageNames' => implode(', ', $state[$key]),
                    ],
                );
            }

            $key = "$checkResultStateKeyPrefix.not-installed";
            if (!empty($state[$key])) {
                $exitCode = 1;
                $this->logger->error(
                    "{count} package is not installed:\n{command}",
                    [
                        'count' => count($state[$key]),
                        'command' => $state["$key.installCommand"] ?? '',
                    ],
                );
            }

            return $exitCode;
        };
    }

    /**
     * @param string $name
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @return array<string>
     */
    protected function parseMultiValueAnnotation(string $name, AnnotationData $annotationData): array
    {
        $value = $annotationData->get($name);

        return $this->utils->explodeCommaSeparatedList($value);
    }

    /**
     * Loop: download, configure, make, make install.
     *
     * @return \Robo\Collection\CollectionBuilder|\Robo\Collection\TaskForEach
     */
    protected function getTaskPhpExtensionsInstall(
        string $opSysStateKey,
        string $instanceStateKey,
        string $extensionsStateKey
    ): TaskInterface {
        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Install PHP extension: {key}')
            ->deferTaskConfiguration('setIterable', $extensionsStateKey)
            ->withBuilder(function (
                CollectionBuilder $builder,
                string $extensionKey,
                $extension
            ) use (
                $taskForEach,
                $opSysStateKey,
                $instanceStateKey
            ) {
                $state = $taskForEach->getState();
                /** @var \Pmkr\Pmkr\Model\Extension $extension */
                /** @var \Pmkr\Pmkr\Model\Instance $instance */
                $instance = $state[$instanceStateKey];
                /** @var \Pmkr\Pmkr\OpSys\OpSys $opSys */
                $opSys = $state[$opSysStateKey];
                $extSrcDir = "{$instance->srcDir}/ext/{$extension->name}";
                $phpBinDir = "{$instance->shareDir}/bin";

                $builder
                    ->addTask(
                        $this
                            ->taskPmkrCollectLibraryDependenciesFromExtension()
                            ->setOpSys($opSys)
                            ->setExtension($extension)
                    )
                    ->addTask($this->getTaskInstallLibraries('libraries'))
                    ->addTask(
                        $this
                            ->taskPmkrPhpExtensionDownloadWrapper()
                            ->setInstance($instance)
                            ->setExtension($extension)
                    )
                    ->addTask(
                        $this
                            ->taskPmkrPhpExtensionCompileWrapper()
                            ->setExtensionSrcDir($extSrcDir)
                            ->setExtension($extension)
                            ->setPhpBinDir($phpBinDir)
                    )
                    ->addTask(
                        $this
                            ->taskPmkrPhpExtensionEtcDeploy()
                            ->setInstance($instance)
                            ->setExtension($extension)
                    );
            });

        return $taskForEach;
    }

    /**
     * Loop: download.
     *
     * @return \Robo\Collection\CollectionBuilder|\Robo\Collection\TaskForEach
     */
    protected function getTaskPmkrPhpExtensionsDownload(
        string $instanceStateKey,
        string $extensionsStateKey
    ): TaskInterface {
        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Install PHP extension: {key}')
            ->deferTaskConfiguration('setIterable', $extensionsStateKey)
            ->withBuilder(function (
                CollectionBuilder $builder,
                string $extensionKey,
                $extension
            ) use (
                $taskForEach,
                $instanceStateKey
            ) {
                $state = $taskForEach->getState();
                /** @var \Pmkr\Pmkr\Model\Extension $extension */
                /** @var \Pmkr\Pmkr\Model\Instance $instance */
                $instance = $state[$instanceStateKey];

                $builder
                    ->addTask(
                        $this
                            ->taskPmkrPhpExtensionDownloadWrapper()
                            ->setInstance($instance)
                            ->setExtension($extension)
                    );
            });

        return $taskForEach;
    }

    protected function getTaskPhpExtensionCompile(
        string $extSrcDirStateKey,
        string $extStateKey,
        string $phpBinDirStateKey
    ): TaskInterface {
        return $this
            ->taskPmkrPhpExtensionCompileWrapper()
            ->deferTaskConfiguration('setExtensionSrcDir', $extSrcDirStateKey)
            ->deferTaskConfiguration('setExtension', $extStateKey)
            ->deferTaskConfiguration('setPhpBinDir', $phpBinDirStateKey);
    }

    protected function getTaskExtensionCompilerPeclBefore(
        string $instanceStateKey,
        string $extensionsStateKey
    ): TaskInterface {
        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Run before script of the {key} PHP extension')
            ->deferTaskConfiguration('setIterable', $extensionsStateKey)
            ->withBuilder(
                function (
                    CollectionBuilder $builder,
                    string $key,
                    Extension $extension
                ) use (
                    $taskForEach,
                    $instanceStateKey
                ) {
                    $state = $taskForEach->getState();
                    /** @var \Pmkr\Pmkr\Model\Instance $instance */
                    $instance = $state[$instanceStateKey];
                    $extensionSrcDir = "$instance->srcDir/ext/$extension->name";
                    $builder->addTask(
                        $this
                            ->taskPmkrBeforeExtensionConfigure()
                            ->setExtensionSrcDir($extensionSrcDir)
                            ->setExtension($extension)
                            ->setPhpBinDir("$instance->shareDir/bin")
                    );
                }
            );

        return $taskForEach;
    }

    protected function getTaskInstallLibraries(
        string $librariesStateKey
    ): TaskInterface {
        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Install library: {key}')
            ->deferTaskConfiguration('setIterable', $librariesStateKey)
            ->withBuilder(function (
                CollectionBuilder $builder,
                string $key,
                $library
            ): void {
                $builder
                    ->addTask(
                        $this
                            ->taskPmkrLibraryInstall()
                            ->setLibrary($library)
                    );
            });

        return $taskForEach;
    }

    /**
     * @return string[]
     */
    protected function humanReadableOutputFormats(): array
    {
        return [
            'table',
        ];
    }

    protected function isHumanReadableOutputFormat(string $format): bool
    {
        return in_array($format, $this->humanReadableOutputFormats());
    }
}
