<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Robo\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pmkr\Pmkr\Utils;
use Robo\Collection\CallableTask;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * @property \Psr\Log\LoggerInterface $logger
 */
trait PmkrCommandsTrait
{

    /**
     * @param \Robo\Symfony\ConsoleIO $io
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    abstract protected function collectionBuilder($io = null);

    /**
     * @command pmkr:instance:install
     *
     * @pmkrInteractServiceKey arg.serviceKey
     * @pmkrInteractInstanceKey arg.instanceKey
     */
    public function cmdPmkrInstanceInstallExecute(
        string $serviceKey,
        string $instanceKey
    ): TaskInterface {
        $cb = $this->collectionBuilder();
        /** @var \Robo\Contract\TaskInterface $collection */
        $collection = $cb->getCollection();

        $processCallback = $this->getProcessCallback();
        $taskContainerStart = new CallableTask(
            function (RoboState $state) use ($serviceKey, $processCallback): int {
                $this->logger->notice(
                    'Start container {service.key}',
                    [
                        'service.key' => $serviceKey,
                    ],
                );
                $process = $this->processFactory->createInstance(
                    [
                        'docker-compose',
                        '--file',
                        './docker-compose.yml',
                        'run',
                        '--rm',
                        '--detach',
                        '--entrypoint',
                        'tail -f /dev/null',
                        $serviceKey,
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);
                $state['container.name'] = trim($process->getOutput());

                return $process->getExitCode();
            },
            $collection,
        );

        $taskContainerStop = new CallableTask(
            function (RoboState $state) use ($processCallback): int {
                $this->logger->notice(
                    'Stop container: {container.name}',
                    [
                        'container.name' => $state['container.name'],
                    ],
                );

                $process = $this->processFactory->createInstance(
                    [
                        'docker',
                        'container',
                        'stop',
                        $state['container.name'],
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);

                return $process->getExitCode();
            },
            $collection,
        );

        $taskInitOs = new CallableTask(
            function (RoboState $state) use ($serviceKey, $processCallback): int {
                $process = $this->processFactory->createInstance(
                    [
                        'docker',
                        'exec',
                        $state['container.name'],
                        'bash',
                        "./tests/_data/Docker/$serviceKey/init.bash",
                    ],
                );

                $this->logger->notice(
                    'Exec in container: {container.name} - {command}',
                    [
                        'container.name' => $state['container.name'],
                        'command' => $process->getCommandLine(),
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);

                return $process->getExitCode();
            },
            $collection,
        );

        $taskInitPmkr = new CallableTask(
            function (RoboState $state) use ($processCallback): int {
                $subCommand = 'SHELL="${SHELL}" ./bin/pmkr init:pmkr --force';

                $command = sprintf(
                    'docker exec %s bash -c %s',
                    escapeshellarg($state['container.name']),
                    escapeshellarg($subCommand),
                );

                $process = $this->processFactory->fromShellCommandline($command);

                $this->logger->notice(
                    'Exec: {command}',
                    [
                        'command' => $process->getCommandLine(),
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);

                return $process->getExitCode();
            },
            $collection,
        );

        $taskInstallPackages = new CallableTask(
            function (RoboState $state) use ($processCallback, $instanceKey): int {
                $subCommand = sprintf(
                    'eval $(pmkr instance:dependency:package:list --format=code --format-code=install-command %s)',
                    escapeshellarg($instanceKey),
                );

                $command = sprintf(
                    'docker exec %s bash -c %s',
                    escapeshellarg($state['container.name']),
                    escapeshellarg($subCommand),
                );

                $process = $this->processFactory->fromShellCommandline($command);

                $this->logger->notice(
                    'Exec: {command}',
                    [
                        'command' => $process->getCommandLine(),
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);

                return $process->getExitCode();
            },
            $collection,
        );

        $taskInstanceInstall = new CallableTask(
            function (RoboState $state) use ($processCallback, $instanceKey): int {
                $process = $this->processFactory->createInstance(
                    [
                        'docker',
                        'exec',
                        $state['container.name'],
                        'pmkr',
                        'instance:install',
                        $instanceKey,
                    ],
                );

                $this->logger->notice(
                    'Exec: {command}',
                    [
                        'command' => $process->getCommandLine(),
                    ],
                );

                $process->setTimeout(null);
                $process->run($processCallback);

                return $process->getExitCode();
            },
            $collection,
        );

        $cb->addTask($taskContainerStart);
        $cb->completion($taskContainerStop);
        $cb->addTask($taskInitOs);
        $cb->addTask($taskInitPmkr);
        $cb->addTask($taskInstallPackages);
        $cb->addTask($taskInstanceInstall);

        return $cb;
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook interact @pmkrInteractServiceKey
     */
    public function onHookInteractServiceKey(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $tag = 'pmkrInteractServiceKey';

        $utils = new Utils($this->getConfig());

        $inputLocator = trim($annotationData->get($tag));
        $inputValue = $utils->getInputValue($input, $inputLocator);

        if ($inputValue) {
            return;
        }

        $io = new SymfonyStyle(
            $input,
            $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output,
        );

        $inputValue = $io->choice(
            "$inputLocator",
            $this->getServiceKeys(),
        );
        $utils->setInputValue($input, $inputLocator, $inputValue);
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook interact @pmkrInteractInstanceKey
     *
     * @link https://github.com/consolidation/annotated-command#interact-hook
     */
    public function onHookInteractInteractInstanceKey(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $tag = 'pmkrInteractInstanceKey';

        $utils = new Utils($this->getConfig());

        $inputLocator = trim($annotationData->get($tag));
        $inputValue = $utils->getInputValue($input, $inputLocator);

        if ($inputValue) {
            return;
        }

        $io = new SymfonyStyle(
            $input,
            $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output,
        );

        $inputValue = $io->choice(
            "$inputLocator",
            $this->getInstanceKeys(),
        );
        $utils->setInputValue($input, $inputLocator, $inputValue);
    }

    /**
     * @return array<int, string>
     */
    protected function getServiceKeys(): array
    {
        /** @var DevDockerCompose $dockerCompose */
        $dockerCompose = Yaml::parseFile('./docker-compose.yml');

        return array_keys($dockerCompose['services'] ?? []);
    }

    /**
     * @return array<int, string>
     */
    protected function getInstanceKeys(): array
    {
        $pmkrConfig = $this->getPmkrConfig();

        return array_keys($pmkrConfig['instances']);
    }

    /**
     * @return array<string, DevPmkrConfig>
     */
    protected function getPmkrConfig(): array
    {
        $command = 'pmkr --no-ansi config:export --format="json"';
        $output = [];
        exec($command, $output);

        return json_decode(implode("\n", $output), true);
    }
}
