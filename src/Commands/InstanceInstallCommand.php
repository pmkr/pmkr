<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Task\EtcDeploy\PhpCoreEtcDeployTaskLoader;
use Pmkr\Pmkr\Task\PhpExtensionCompile\TaskLoader as PhpExtensionCompileTaskLoader;
use Pmkr\Pmkr\Task\PhpExtensionDownload\TaskLoader as PhpExtensionDownloadTaskLoader;
use Pmkr\Pmkr\Task\Patch\TaskLoader as PatchTaskLoader;
use Pmkr\Pmkr\TaskOverride\Archive\ExtractTaskLoader;
use Pmkr\Pmkr\Util\Filter\PhpExtensionFilter;
use Pmkr\Pmkr\Util\PhpCoreCompileConfigureCommandBuilder;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboState;
use Robo\Task\Base\Tasks as ExecTaskLoader;
use Robo\Task\Filesystem\Tasks as FilesystemTaskLoader;
use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Process\Process;

class InstanceInstallCommand extends CommandBase
{
    use PhpExtensionDownloadTaskLoader;
    use PhpExtensionCompileTaskLoader;
    use PhpCoreEtcDeployTaskLoader;
    use PatchTaskLoader;
    use ExecTaskLoader;
    use ExtractTaskLoader;
    use FilesystemTaskLoader;

    protected PhpCoreCompileConfigureCommandBuilder $phpCoreCompileConfigureCommandBuilder;

    protected function initDependencies()
    {
        if (!$this->initialized) {
            parent::initDependencies();
            $container = $this->getContainer();
            $this->phpCoreCompileConfigureCommandBuilder = $container
                ->get('pmkr.php_core.compile_configure_command.builder');
        }

        return $this;
    }

    /**
     * Compiles and installs a PHP core with the enabled and with the optional
     * extensions as well.
     *
     * @param string $instanceName
     *   Instance name is a key in the pmkr.*.yml#/instances array.
     *   Available options can be listed with `pmkr instance:list` command.
     *
     * @command instance:install
     *
     * @aliases ii
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName arg.instanceName
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstanceName arg.instanceName
     */
    public function cmdInstallExecute(string $instanceName): TaskInterface
    {
        $cb = $this->collectionBuilder();
        $this->addTasksPhpCoreInstall(
            $cb,
            $instanceName,
            'extensions',
        );
        $cb->addTask(
            $this->getTaskPhpExtensionsInstall(
                'opSys',
                'instance',
                'optionalExtensions',
            )
        );

        return $cb;
    }

    /**
     * Compiles and installs a PHP core only with the enabled extensions.
     *
     * Optional extensions can be installed separately with the
     * `pmkr instance:install:ext-optional` command.
     *
     * @param string $instanceName
     *   Instance name is a key in the pmkr.yml#/instances array.
     *
     * @command instance:install:core
     *
     * @aliases iic
     *
     * @usage 0704-nts
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName arg.instanceName
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstanceName arg.instanceName
     */
    public function cmdInstallCoreExecute(string $instanceName): CollectionBuilder
    {
        $cb = $this->collectionBuilder();
        $this->addTasksPhpCoreInstall(
            $cb,
            $instanceName,
            'enabledExtensions',
        );

        return $cb;
    }

    /**
     * Compiles and installs only the optional extensions defined in the
     * extensionSet.
     *
     * @param string $instanceName
     *   The given instance has to be already available.
     *   Default value: the current instance.
     *
     * @command instance:install:ext-optional
     *
     * @aliases iio
     *
     * @usage 0704-nts
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInitCurrentInstanceName arg.instanceName
     * @pmkrInteractInstanceName
     *   arg.instanceName:
     *     hasShareDir: true
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstanceName
     *   arg.instanceName:
     *     hasShareDir: true
     */
    public function cmdInstanceInstallExtOptionalExecute(string $instanceName): CollectionBuilder
    {
        $cb = $this->collectionBuilder();
        $cb
            ->addCode($this->getTaskPhpCoreInstallInit($instanceName))
            ->addCode($this->getTaskOsDetector())
            ->addTask(
                $this
                    ->taskPmkrCollectPackageDependenciesFromExtensions()
                    ->deferTaskConfiguration('setOpSys', 'opSys')
                    ->deferTaskConfiguration('setExtensions', 'optionalExtensions')
            )
            ->addCode($this->getTaskPackageManagerFactory(
                'opSys',
                'packageManager',
            ))
            ->addCode($this->getTaskCollectMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
                'packageManager.checkResult',
            ))
            ->addCode($this->getTaskCheckMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
            ))
            ->addTask($this->getTaskPhpExtensionsInstall(
                'opSys',
                'instance',
                'optionalExtensions',
            ));

        return $cb;
    }

    /**
     * Installs extra extensions for the given instance.
     *
     * @param string $instanceName
     *   A key from the pmkr.yml#/instances array.
     * @param array $extensionNames
     *   Keys from the pmkr.yml#/extensions array.
     *   Comma separated list or multiple argument.
     *
     * @command instance:install:ext-custom
     *
     * @aliases iiu
     *
     * @usage 0704-nts redis,memcached
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName
     *   arg.instanceName:
     *     hasSrcDir: true
     *     hasShareDir: true
     * @pmkrValidateInstanceName
     *   arg.instanceName:
     *     hasSrcDir: true
     *     hasShareDir: true
     * @pmkrNormalizeCommaSeparatedList arg.extensionNames
     */
    public function cmdInstanceInstallExtCustomExecute(
        string $instanceName,
        array $extensionNames,
        array $options = []
    ) {
        return $this
            ->collectionBuilder()
            ->addCode(function (RoboState $state) use ($instanceName, $extensionNames): int {
                $state['pmkr'] = PmkrConfig::__set_state([
                    'config' => $this->getConfig(),
                    'configPath' => [],
                ]);
                $state['instance'] = $state['pmkr']->instances[$instanceName];
                $state['extensionNames'] = $extensionNames;

                return 0;
            })
            ->addCode($this->getTaskOsDetector())
            ->addCode($this->getTaskResolveExtensionNames(
                'instance',
                'extensionNames',
                'extensionNameMapping',
            ))
            ->addCode($this->getTaskValidateExtensionNameMapping(
                'instance',
                'extensionNameMapping',
                'extensions',
            ))
            ->addTask($this->getTaskExtensionsPackageDependencyCollector(
                'extensions',
                'opSys',
            ))
            ->addCode($this->getTaskPackageManagerFactory(
                'opSys',
                'packageManager',
            ))
            ->addCode($this->getTaskCollectMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
                'packageManager.checkResult',
            ))
            ->addCode($this->getTaskCheckMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
            ))
            ->addTask($this->getTaskPhpExtensionsInstall(
                'opSys',
                'instance',
                'extensions',
            ));
    }

    /**
     * - init.
     * - dependency detection.
     * - core download.
     * - enabled extensions download.
     * - compile.
     * - install.
     * - core etc deploy.
     * - enabled extensions etc deploy.
     *
     * @return $this
     */
    protected function addTasksPhpCoreInstall(
        CollectionBuilder $cb,
        string $instanceName,
        string $extensionsToCollectDependenciesForStateKey
    ) {
        $cb
            ->addCode($this->getTaskPhpCoreInstallInit($instanceName))
            ->addCode($this->getTaskOsDetector())
            ->addTask(
                $this
                    ->taskPmkrCollectPackageDependenciesFromInstance()
                    ->deferTaskConfiguration('setOpSys', 'opSys')
                    ->deferTaskConfiguration('setInstance', 'instance')
                    ->deferTaskConfiguration('setExtensions', $extensionsToCollectDependenciesForStateKey)
            )
            ->addCode($this->getTaskPackageManagerFactory(
                'opSys',
                'packageManager',
            ))
            ->addCode($this->getTaskCollectMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
                'packageManager.checkResult',
            ))
            ->addCode($this->getTaskCheckMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
            ))
            ->addTask(
                $this
                    ->taskPmkrCollectLibraryDependenciesFromInstance()
                    ->deferTaskConfiguration('setOpSys', 'opSys')
                    ->deferTaskConfiguration('setInstance', 'instance')
                    ->deferTaskConfiguration('setExtensions', 'enabledExtensions')
            )
            ->addCode($this->getTaskCheckMissingPackageDependencies())
            ->addTask($this->getTaskInstallLibraries('libraries'))
            ->addCode($this->getTaskPhpCoreDownloadPrepare(
                'instance',
                'phpCoreDownload',
            ))
            ->addTask($this->getTaskPhpCoreDownload(
                'phpCoreDownload',
            ))
            ->addTask($this->getTaskPhpCoreExtractPrepare(
                'phpCoreDownload.destination.src',
            ))
            ->addTask($this->getTaskPhpCoreExtractFromCacheToSrc(
                'phpCoreDownload.destination.cache',
                'phpCoreDownload.destination.src',
            ))
            ->addCode($this->getTaskPhpCoreApplyPatchesPrepare('opSys'))
            ->addTask($this->getTaskApplyPatches(
                'instanceSrcDir',
                'corePatches',
            ))
            ->addTask($this->getTaskPmkrPhpExtensionsDownload(
                'instance',
                'enabledExtensions',
            ))
            ->addTask($this->getTaskExtensionCompilerPeclBefore(
                'instance',
                'enabledExtensions',
            ));

        $this
            ->addTasksPhpCoreCompileConfigure(
                $cb,
                'instance',
                'instanceSrcDir'
            )
            ->addTasksPhpCoreCompileMake(
                $cb,
                'instanceSrcDir',
            );

        $cb->addTask(
            $this
                ->taskPmkrPhpCoreEtcDeploy()
                ->deferTaskConfiguration('setInstance', 'instance')
        );

        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Deploy etc files for extension: {key}')
            ->deferTaskConfiguration('setIterable', 'enabledExtensions')
            ->withBuilder(function (CollectionBuilder $builder, $extensionKey, $extension) use ($taskForEach) {
                $state = $taskForEach->getState();
                $builder->addTask(
                    $this
                        ->taskPmkrPhpExtensionEtcDeploy()
                        ->setInstance($state['instance'])
                        ->setExtension($extension)
                );
            });
        $cb->addTask($taskForEach);

        return $this;
    }

    protected function getTaskPhpCoreApplyPatchesPrepare(
        string $opSysStateKey
    ): \Closure {
        return function (RoboState $state) use ($opSysStateKey): int {
            $this->logger->notice('PMKR - PHP core - apply patches - prepare');

            $pmkr = $this->getPmkr();
            /** @var \Pmkr\Pmkr\Model\Instance $instance */
            $instance = $state['instance'];
            /** @var \Pmkr\Pmkr\OpSys\OpSys $opSys */
            $opSys = $state[$opSysStateKey];

            $corePatchFilter = $this->getContainer()->get('pmkr.patch.filter');
            $corePatchFilter->setVersion($instance->coreVersion);
            $corePatchFilter->setOpSys($opSys);

            $patches = array_intersect_key(
                iterator_to_array($pmkr->patches->getIterator()),
                array_filter($instance->core->patchList),
            );

            $state['corePatches'] = array_filter($patches, $corePatchFilter);

            return 0;
        };
    }

    protected function getTaskApplyPatches(string $srcDirStateKey, $patchesStateKey)
    {
        $taskForEach = $this->taskForEach();
        $taskForEach
            ->iterationMessage('Apply patch: {key}')
            ->deferTaskConfiguration('setIterable', $patchesStateKey)
            ->withBuilder(function (
                CollectionBuilder $builder,
                string $key,
                $patch
            ) use (
                $taskForEach,
                $srcDirStateKey
            ) {
                /** @var \Pmkr\Pmkr\Model\Patch $patch */
                $state = $taskForEach->getState();
                $srcDir = $state[$srcDirStateKey];

                $builder
                    ->addTask(
                        $this
                            ->taskPmkrPatchApply()
                            ->setSrcDir($srcDir)
                            ->setPatch($patch)
                    );
            });

        return $taskForEach;
    }

    /**
     * Adds several things to $state.
     */
    protected function getTaskPhpCoreDownloadPrepare(string $instanceStateKey, string $stateKeyPrefix): \Closure
    {
        return function (RoboState $state) use ($instanceStateKey, $stateKeyPrefix): int {
            $config = $this->getConfig();

            /** @var \Pmkr\Pmkr\Model\Instance $instance */
            $instance = $state[$instanceStateKey];
            $state["$stateKeyPrefix.uri"] = $this->utils->getPhpCoreDownloadUri($instance->coreVersionNumber);
            $state["$stateKeyPrefix.destination.cache"] = $this->utils->phpCoreCacheDestination(
                $config,
                $state["$stateKeyPrefix.uri"],
            );
            $state["$stateKeyPrefix.hashChecksum"] = $instance->coreChecksum->hashChecksum;
            $state["$stateKeyPrefix.hashOptions"] = [
                'hashAlgorithm' => $instance->coreChecksum->hashAlgorithm ?? '',
                'hashFlags' => $instance->coreChecksum->hashFlags ?? 0,
                'hashKey' => $instance->coreChecksum->hashKey ?? '',
            ];
            $state["$stateKeyPrefix.destination.src"] = $instance->srcDir;

            return 0;
        };
    }

    protected function getTaskPhpCoreDownload(string $stateKeyPrefix): TaskInterface
    {
        return $this
            ->taskDownloadCurl()
            ->deferTaskConfiguration('setUri', "$stateKeyPrefix.uri")
            ->deferTaskConfiguration('setDestination', "$stateKeyPrefix.destination.cache")
            ->deferTaskConfiguration('setHashChecksum', "$stateKeyPrefix.hashChecksum")
            ->deferTaskConfiguration('setHashOptions', "$stateKeyPrefix.hashOptions");
    }

    protected function getTaskPhpCoreExtractPrepare(string $dstStateKey): TaskInterface
    {
        return $this
            ->taskFilesystemStack()
            ->deferTaskConfiguration('remove', $dstStateKey);
    }

    protected function getTaskPhpCoreExtractFromCacheToSrc(string $packPathStateKey, string $dstStateKey): TaskInterface
    {
        return $this
            ->taskExtract('')
            ->deferTaskConfiguration('fileName', $packPathStateKey)
            ->deferTaskConfiguration('to', $dstStateKey);
    }

    /**
     * @return $this
     */
    protected function addTasksPhpCoreCompileConfigure(
        CollectionBuilder $cb,
        string $instanceStateKey,
        string $instanceSrcDirStateKey
    ) {
        $cb
            ->addTask(
                $this
                    ->taskExec('rm ./configure')
                    ->deferTaskConfiguration('dir', $instanceSrcDirStateKey)
            )
            ->addTask(
                $this
                    ->taskExec('./buildconf --force')
                    ->deferTaskConfiguration('dir', $instanceSrcDirStateKey)
            )
            ->addCode(function (RoboState $state) use ($instanceStateKey): int {
                $state['phpCore.configureCommand'] = $this
                    ->phpCoreCompileConfigureCommandBuilder
                    ->build($state[$instanceStateKey]);

                return 0;
            })
            ->addCode(function (RoboState $state) use ($instanceSrcDirStateKey): int {
                $this->logger->info(
                    'Running {command} in {cwd}',
                    [
                        'command' => $state['phpCore.configureCommand'],
                        'cwd' => $state[$instanceSrcDirStateKey],
                    ],
                );

                $process = Process::fromShellCommandline(
                    $state['phpCore.configureCommand'],
                    $state[$instanceSrcDirStateKey],
                    null,
                    null,
                    null,
                );
                $exitCode = $process->run($this->utils->getProcessCallback($this->output()));
                if ($exitCode) {
                    return $exitCode;
                }

                $stdError = $process->getErrorOutput();
                $configureWarning = 'configure: WARNING: unrecognized options:';
                if (strpos($stdError, $configureWarning) !== false) {
                    $this->logger->error($configureWarning);

                    return 1;
                }

                return 0;
            });

        return $this;
    }

    /**
     * @return $this
     */
    protected function addTasksPhpCoreCompileMake(
        CollectionBuilder $cb,
        string $instanceSrcDirStateKey
    ) {
        $cb
            ->addTask(
                $this
                    ->taskExec('make clean || true')
                    ->deferTaskConfiguration('dir', $instanceSrcDirStateKey)
            )
            ->addTask(
                $this
                    ->taskExec('make -j "$(nproc)"')
                    ->deferTaskConfiguration('dir', $instanceSrcDirStateKey)
            )
            ->addTask(
                $this
                    ->taskExec('make install')
                    ->deferTaskConfiguration('dir', $instanceSrcDirStateKey)
            );

        return $this;
    }

    protected function getTaskResolveExtensionNames(
        string $instanceStateKey,
        string $extNamesStateKey,
        string $dstStateKey
    ): \Closure {
        return function (RoboState $state) use ($instanceStateKey, $extNamesStateKey, $dstStateKey): int {
            /** @var \Pmkr\Pmkr\Model\Instance $instance */
            $instance = $state[$instanceStateKey];
            $extNames = $state[$extNamesStateKey];
            $versionNumber = VersionNumber::createFromString($instance->coreVersion);
            $extensions = $this->getConfig()->get('extensions');
            $state[$dstStateKey] = [];
            foreach ($extNames as $extName) {
                $candidates = $this->utils->nameCandidates($extName, $versionNumber, $instance['isZts'], '');
                $extensionKey = $this->utils->findCandidate($candidates, $extensions);
                $state[$dstStateKey][$extName] = $extensionKey;
            }

            return 0;
        };
    }

    protected function getTaskValidateExtensionNameMapping(
        string $instanceStateKey,
        string $mappingStateKey,
        string $dstStateKey
    ): \Closure {
        return function (
            RoboState $state
        ) use (
            $instanceStateKey,
            $mappingStateKey,
            $dstStateKey
        ): int {
            $mapping = $state[$mappingStateKey];
            $missing = array_keys($mapping, null, true);
            if ($missing) {
                $this->logger->error(
                    'The following extension names could not be resolved: {extensionNames}',
                    [
                        'extensionNames' => implode(', ', $missing),
                    ],
                );

                return 1;
            }

            /** @var \Pmkr\Pmkr\Model\Instance $instance */
            $instance = $state['instance'];

            /** @var PmkrConfig $pmkr */
            $pmkr = $state['pmkr'];

            $extensions = array_intersect_key(
                iterator_to_array($pmkr->extensions->getIterator()),
                array_flip($mapping),
            );

            $filter = new PhpExtensionFilter();
            // @todo Maybe the ignore filter should be the other way around.
            $filter->setIgnore([$instance->isZts ? 'nts' : 'zts']);
            $ignoredExtensions = array_filter($extensions, $filter);
            if ($ignoredExtensions) {
                $extKeys = array_keys($ignoredExtensions);
                $extKeysList = implode(', ', $extKeys);

                $confirmed = false;
                if ($this->input()->isInteractive()) {
                    $confirmed = $this->io->confirm(
                        "Following extension will be ignored because of the ZTS vs NTS: $extKeysList",
                        false,
                    );
                }

                if (!$confirmed) {
                    return 2;
                }
            }

            $extensions = array_diff_key($extensions, $ignoredExtensions);
            if (!$extensions) {
                $this->logger->error('At least one extension name is required');

                return 3;
            }

            $state[$dstStateKey] = $extensions;

            return 0;
        };
    }
}
