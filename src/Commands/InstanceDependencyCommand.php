<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Pmkr\Pmkr\CodeResult\CodeCommandResult;
use Pmkr\Pmkr\CodeResult\CodeResult;
use Robo\Robo;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Output\NullOutput;

class InstanceDependencyCommand extends CommandBase
{

    /**
     * List package dependencies of the given instance.
     *
     * @param string $instanceName
     *   Name of the instance.
     *
     * @option string $format
     *   This controls what the output should be look like.
     * @option string $format-code
     *   Only comes into play when the --format is "code".
     *   Available values:
     *   - install-command
     *
     * @command instance:dependency:package:list
     *
     * @usage --no-ansi --format='code' --format='install-command'
     *   This suitable form `eval "$(above)"`
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName arg.instanceName
     * @pmkrValidateInstanceName arg.instanceName
     */
    public function cmdInstanceDependencyPackageListExecute(
        string $instanceName,
        array $options = [
            'format' => 'list',
            'format-code' => 'install-command'
        ]
    ): CommandResult {
        $io = new ConsoleIO(Robo::input(), new NullOutput());

        $result = $this
            ->collectionBuilder($io)
            ->addCode($this->getTaskPhpCoreInstallInit($instanceName))
            ->addCode($this->getTaskOsDetector())
            ->addTask($this->getTaskCollectPackageDependenciesFromInstance(
                'instance',
                'opSys',
            ))
            ->addCode($this->getTaskPackageManagerFactory(
                'opSys',
                'packageManager',
            ))
            ->addTask($this->getTaskCollectPackageDependenciesFromInstance(
                'instance',
                'opSys',
            ))
            ->addCode($this->getTaskCollectMissingPackageDependencies(
                'packageManager',
                'packageManager.packages',
                'packageManager.checkResult',
            ))
            ->run();

        $assets = $result->getData();
        $packageNames = $assets["packageManager.checkResult.not-installed"] ?? [];

        if (!$packageNames) {
            $this->logger->info('There is no missing package');
        }

        return CommandResult::data(array_keys($packageNames));
    }

    /**
     * @hook alter instance:dependency:package:list
     *
     * @link https://github.com/consolidation/annotated-command#alter-hook
     */
    public function cmdInstanceDependencyPackageListAlter(
        $result,
        CommandData $commandData
    ) {
        if ($result instanceof CommandResult) {
            $data = $result->getOutputData();
        } elseif (is_array($result)) {
            $data = $result;
        } else {
            return $result;
        }

        $format = $commandData->input()->getOption('format');
        if ($format === 'code') {
            $codeResult = new CodeResult();
            $formatCode = $commandData->input()->getOption('format-code');

            $config = $this->getConfig();
            $shell = pathinfo(
                (string) $config->get('env.SHELL'),
                PATHINFO_BASENAME,
            );

            switch ($formatCode) {
                case 'install-command':
                    $opSys = $this->getContainer()->get('pmkr.op_sys');
                    $pmFactory = $this->getContainer()->get('pmkr.package_manager.factory');
                    $handlerName = $opSys->packageManager();
                    $handlerConfig = (array) $config->get("packageManagers.$handlerName.config");
                    $handler = $pmFactory->createInstance($handlerName, $handlerConfig);
                    $codeResult->language = $shell ?: 'zsh';
                    $codeResult->code = $handler->installCommand($data);
                    break;
            }

            $result = CodeCommandResult::data($codeResult);
        }

        return $result;
    }
}
