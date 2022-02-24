<?php

declare(strict_types = 1);

use Consolidation\AnnotatedCommand\CommandResult;
use League\Container\Container as LeagueContainer;
use NuvoleWeb\Robo\Task\Config\Robo\loadTasks as ConfigLoader;
use Pmkr\Pmkr\Tests\Robo\Commands\BuildCommandsTrait;
use Pmkr\Pmkr\Util\ProcessFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Collection\CallableTask;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboState;
use Robo\Tasks;
use Robo\Task\Docker\Tasks as DockerTaskLoader;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Sweetchuck\Robo\Phpstan\PhpstanTaskLoader;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ConfigLoader;
    use GitTaskLoader;
    use PhpcsTaskLoader;
    use PhpstanTaskLoader;
    use DockerTaskLoader;
    use BuildCommandsTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $composerInfo = [];

    /**
     * @var array<string, mixed>
     */
    protected array $codeceptionInfo = [];

    protected string $shell = '/bin/bash';

    /**
     * @var string[]
     */
    protected array $codeceptionSuiteNames = [];

    protected string $packageVendor = '';

    protected string $packageName = '';

    protected string $binDir = 'vendor/bin';

    protected string $gitHook = '';

    protected string $envVarNamePrefix = '';

    /**
     * Allowed values: dev, ci, prod.
     */
    protected string $environmentType = '';

    /**
     * Allowed values: local, jenkins, travis.
     */
    protected string $environmentName = '';

    protected ProcessFactory $processFactory;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->processFactory = new ProcessFactory();

        $this
            ->initShell()
            ->initComposerInfo()
            ->initEnvVarNamePrefix()
            ->initEnvironmentTypeAndName();
    }

    /**
     * @hook pre-command @initLintReporters
     */
    public function onHookPreCommandInitLintReporters(): void
    {
        $lintServices = BaseReporter::getServices();
        $container = $this->getContainer();
        if (!($container instanceof LeagueContainer)) {
            return;
        }

        foreach ($lintServices as $name => $class) {
            if ($container->has($name)) {
                continue;
            }

            $container->add($name, $class);
            $container->extend($name)->setShared(false);
        }
    }

    /**
     * Git "pre-commit" hook callback.
     *
     * @command githook:pre-commit
     *
     * @hidden
     *
     * @initLintReporters
     */
    public function githookPreCommit(): CollectionBuilder
    {
        $this->gitHook = 'pre-commit';

        return $this
            ->collectionBuilder()
            ->addTask($this->getTaskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskCodeceptRunSuites());
    }

    /**
     * @option string $format
     *   Default: yaml
     *
     * @command config:export
     */
    public function cmdConfigExportExecute(): CommandResult
    {
        return CommandResult::data($this->getConfig()->export());
    }

    /**
     * Run the Robo unit tests.
     *
     * @param array<string> $suiteNames
     *
     * @command test
     */
    public function test(array $suiteNames): CollectionBuilder
    {
        $this->validateArgCodeceptionSuiteNames($suiteNames);

        return $this->getTaskCodeceptRunSuites($suiteNames);
    }

    /**
     * Run code style checkers.
     *
     * @command lint
     *
     * @initLintReporters
     */
    public function lint(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTask($this->getTaskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskPhpstanAnalyze());
    }

    /**
     * @command lint:phpcs
     *
     * @initLintReporters
     */
    public function cmdLintPhpcsExecute(): TaskInterface
    {
        return $this->getTaskPhpcsLint();
    }

    /**
     * @command lint:phpstan
     *
     * @initLintReporters
     */
    public function cmdLintPhpstanExecute(): TaskInterface
    {
        return $this->getTaskPhpstanAnalyze();
    }

    protected function getTaskPhpstanAnalyze(): TaskInterface
    {
        /** @var \Sweetchuck\LintReport\Reporter\VerboseReporter $verboseReporter */
        $verboseReporter = $this->getContainer()->get('lintVerboseReporter');
        $verboseReporter->setFilePathStyle('relative');

        return $this
            ->taskPhpstanAnalyze()
            ->setNoProgress(true)
            ->setNoInteraction(true)
            ->setMemoryLimit('512M')
            ->setErrorFormat('json')
            ->addLintReporter('lintVerboseReporter', $verboseReporter);
    }

    /**
     * @param array{
     *     format: string,
     * } $options
     *
     * @command requirements
     */
    public function cmdRequirementsExecute(
        array $options = [
            'format' => 'list',
        ]
    ): CommandResult {
        $jsonFileName = getenv('COMPOSER') ?: 'composer.json';
        $lockFileName = preg_replace('/\.json$/', '.lock', $jsonFileName);
        $lock = json_decode(file_get_contents($lockFileName) ?: '{}', true);
        $phpExtensions = $this->fetchExtensions($lock['platform'] ?? []);
        foreach ($lock['packages'] as $package) {
            $phpExtensions += $this->fetchExtensions($package['require'] ?? []);
        }

        ksort($phpExtensions);

        return CommandResult::data(array_keys($phpExtensions));
    }

    /**
     * @command pmkr:instance:install
     */
    public function cmdPmkrInstanceInstallExecute(
        string $serviceKey,
        string $instanceKey
    ): TaskInterface {
        $cb = $this->collectionBuilder();
        /** @var TaskInterface $collection */
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

        $taskInit = new CallableTask(
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
        $cb->addTask($taskInit);
        $cb->addTask($taskInstallPackages);
        $cb->addTask($taskInstanceInstall);

        return $cb;
    }

    /**
     * @command php-core:release:list
     *
     * @option string $format
     *   Default: yaml
     */
    public function cmdPhpCoreReleaseListExecute(): CommandResult
    {
        $releases = file_get_contents('https://www.php.net/releases/?json');
        if ($releases === false) {
            return CommandResult::exitCode(1);
        }

        return CommandResult::data(json_decode($releases, true));
    }

    /**
     * @param iterable<string, string> $requirements
     *
     * @return array<string, string>
     */
    protected function fetchExtensions(iterable $requirements): array
    {
        $phpExtensions = [];
        foreach ($requirements as $packageName => $version) {
            $matches = [];
            preg_match('@^ext-(?P<name>[^/]+)$@', $packageName, $matches);
            if (!empty($matches['name'])) {
                $extName = (string) $matches['name'];
                $phpExtensions[$extName] = $version;
            }
        }

        return $phpExtensions;
    }

    protected function errorOutput(): ?OutputInterface
    {
        $output = $this->output();

        return ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;
    }

    /**
     * @return $this
     */
    protected function initEnvVarNamePrefix()
    {
        $this->envVarNamePrefix = strtoupper(str_replace('-', '_', $this->packageName));

        return $this;
    }

    /**
     * @return $this
     */
    protected function initEnvironmentTypeAndName()
    {
        $this->environmentType = (string) getenv($this->getEnvVarName('environment_type'));
        $this->environmentName = (string) getenv($this->getEnvVarName('environment_name'));

        if (!$this->environmentType) {
            if (getenv('CI') === 'true') {
                // CircleCI, Travis and GitLab.
                $this->environmentType = 'ci';
            } elseif (getenv('JENKINS_HOME')) {
                $this->environmentType = 'ci';
                if (!$this->environmentName) {
                    $this->environmentName = 'jenkins';
                }
            }
        }

        if (!$this->environmentName && $this->environmentType === 'ci') {
            if (getenv('GITLAB_CI') === 'true') {
                $this->environmentName = 'gitlab';
            } elseif (getenv('TRAVIS') === 'true') {
                $this->environmentName = 'travis';
            } elseif (getenv('CIRCLECI') === 'true') {
                $this->environmentName = 'circle';
            }
        }

        if (!$this->environmentType) {
            $this->environmentType = 'dev';
        }

        if (!$this->environmentName) {
            $this->environmentName = 'local';
        }

        return $this;
    }

    protected function getEnvVarName(string $name): string
    {
        return "{$this->envVarNamePrefix}_" . strtoupper($name);
    }

    /**
     * @return $this
     */
    protected function initShell()
    {
        $this->shell = getenv('SHELL') ?: '/bin/bash';

        return $this;
    }

    /**
     * @return $this
     */
    protected function initComposerInfo()
    {
        if ($this->composerInfo) {
            return $this;
        }

        $composerFile = getenv('COMPOSER') ?: 'composer.json';
        $composerContent = file_get_contents($composerFile);
        if ($composerContent === false) {
            return $this;
        }

        $this->composerInfo = json_decode($composerContent, true);
        [$this->packageVendor, $this->packageName] = explode('/', $this->composerInfo['name']);

        if (!empty($this->composerInfo['config']['bin-dir'])) {
            $this->binDir = $this->composerInfo['config']['bin-dir'];
        }

        return $this;
    }

    /**
     * @return \Robo\Collection\CollectionBuilder|\Robo\Task\Composer\Validate
     */
    protected function getTaskComposerValidate()
    {
        $composerExecutable = $this->getConfig()->get('composerExecutable');

        return $this->taskComposerValidate($composerExecutable);
    }

    /**
     * @return $this
     */
    protected function initCodeceptionInfo()
    {
        if ($this->codeceptionInfo) {
            return $this;
        }

        $default = [
            'paths' => [
                'tests' => 'tests',
                'log' => 'tests/_log',
            ],
        ];
        $dist = [];
        $local = [];

        if (is_readable('codeception.dist.yml')) {
            $dist = Yaml::parse(file_get_contents('codeception.dist.yml') ?: '{}');
        }

        if (is_readable('codeception.yml')) {
            $local = Yaml::parse(file_get_contents('codeception.yml') ?: '{}');
        }

        $this->codeceptionInfo = array_replace_recursive($default, $dist, $local);

        return $this;
    }

    /**
     * @param array<string> $suiteNames
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function getTaskCodeceptRunSuites(array $suiteNames = []): CollectionBuilder
    {
        if (!$suiteNames) {
            $suiteNames = ['all'];
        }

        $phpExecutables = $this->getEnabledPhpExecutables();
        $cb = $this->collectionBuilder();
        foreach ($suiteNames as $suiteName) {
            foreach ($phpExecutables as $phpExecutable) {
                $cb->addTask($this->getTaskCodeceptRunSuite($suiteName, $phpExecutable));
            }
        }

        return $cb;
    }

    /**
     * @param DevPhpExecutable $php
     */
    protected function getTaskCodeceptRunSuite(string $suite, array $php): CollectionBuilder
    {
        $this->initCodeceptionInfo();

        $withCoverageHtml = $this->environmentType === 'dev';
        $withCoverageXml = $this->environmentType === 'ci';

        $withUnitReportHtml = $this->environmentType === 'dev';
        $withUnitReportXml = $this->environmentType === 'ci';

        $logDir = $this->getLogDir();

        $cmdPattern = '';
        $cmdArgs = [];
        foreach ($php['envVars'] ?? [] as $envName => $envValue) {
            $cmdPattern .= "{$envName}";
            if ($envValue === null) {
                $cmdPattern .= ' ';
            } else {
                $cmdPattern .= '=%s ';
                $cmdArgs[] = escapeshellarg($envValue);
            }
        }

        $cmdPattern .= '%s';
        $cmdArgs[] = $php['command'];

        $cmdPattern .= ' %s';
        $cmdArgs[] = escapeshellcmd("{$this->binDir}/codecept");

        $cmdPattern .= ' --ansi';
        $cmdPattern .= ' --verbose';
        $cmdPattern .= ' --debug';

        $cb = $this->collectionBuilder();
        if ($withCoverageHtml) {
            $cmdPattern .= ' --coverage-html=%s';
            $cmdArgs[] = escapeshellarg("human/coverage/$suite/html");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/human/coverage/$suite")
            );
        }

        if ($withCoverageXml) {
            $cmdPattern .= ' --coverage-xml=%s';
            $cmdArgs[] = escapeshellarg("machine/coverage/$suite/coverage.xml");
        }

        if ($withCoverageHtml || $withCoverageXml) {
            $cmdPattern .= ' --coverage=%s';
            $cmdArgs[] = escapeshellarg("machine/coverage/$suite/coverage.serialized");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/machine/coverage/$suite")
            );
        }

        if ($withUnitReportHtml) {
            $cmdPattern .= ' --html=%s';
            $cmdArgs[] = escapeshellarg("human/junit/junit.$suite.html");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/human/junit")
            );
        }

        if ($withUnitReportXml) {
            $cmdPattern .= ' --xml=%s';
            $cmdArgs[] = escapeshellarg("machine/junit/junit.$suite.xml");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/machine/junit")
            );
        }

        $cmdPattern .= ' run';
        if ($suite !== 'all') {
            $cmdPattern .= ' %s';
            $cmdArgs[] = escapeshellarg($suite);
        }

        $envDir = $this->codeceptionInfo['paths']['envs'];
        $envFileName = "{$this->environmentType}.{$this->environmentName}";
        if (file_exists("$envDir/$envFileName.yml")) {
            $cmdPattern .= ' --env %s';
            $cmdArgs[] = escapeshellarg($envFileName);
        }

        if ($this->environmentType === 'ci' && $this->environmentName === 'jenkins') {
            // Jenkins has to use a post-build action to mark the build "unstable".
            $cmdPattern .= ' || [[ "${?}" == "1" ]]';
        }

        $command = vsprintf($cmdPattern, $cmdArgs);

        return $cb
            ->addCode(function () use ($command, $php) {
                $this->output()->writeln(strtr(
                    '<question>[{name}]</question> runs <info>{command}</info>',
                    [
                        '{name}' => 'Codeception',
                        '{command}' => $command,
                    ]
                ));

                $process = Process::fromShellCommandline(
                    $command,
                    null,
                    $php['envVars'] ?? null,
                    null,
                    null,
                );

                return $process->run(function ($type, $data) {
                    switch ($type) {
                        case Process::OUT:
                            $this->output()->write($data);
                            break;

                        case Process::ERR:
                            $this->errorOutput()->write($data);
                            break;
                    }
                });
            });
    }

    /**
     * @return \Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskPhpcsLint()
    {
        $options = [
            'failOn' => 'warning',
            'lintReporters' => [
                'lintVerboseReporter' => null,
            ],
        ];

        $logDir = $this->getLogDir();
        if ($this->environmentType === 'ci' && $this->environmentName === 'jenkins') {
            $options['failOn'] = 'never';
            $options['lintReporters']['lintCheckstyleReporter'] = $this
                ->getContainer()
                ->get('lintCheckstyleReporter')
                ->setDestination("$logDir/machine/checkstyle/phpcs.psr2.xml");
        }

        if ($this->gitHook === 'pre-commit') {
            return $this
                ->collectionBuilder()
                ->addTask($this
                    ->taskPhpcsParseXml()
                    ->setAssetNamePrefix('phpcsXml.'))
                ->addTask($this
                    ->taskGitListStagedFiles()
                    ->setPaths(['*.php' => true])
                    ->setDiffFilter(['d' => false])
                    ->setAssetNamePrefix('staged.'))
                ->addTask($this
                    ->taskGitReadStagedFiles()
                    ->setCommandOnly(true)
                    ->setWorkingDirectory('.')
                    ->deferTaskConfiguration('setPaths', 'staged.fileNames'))
                ->addTask($this
                    ->taskPhpcsLintInput($options)
                    ->deferTaskConfiguration('setFiles', 'files')
                    ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
        }

        return $this->taskPhpcsLintFiles($options);
    }

    protected function getLogDir(): string
    {
        $this->initCodeceptionInfo();

        return !empty($this->codeceptionInfo['paths']['log']) ?
            $this->codeceptionInfo['paths']['log']
            : 'tests/_log';
    }

    /**
     * @return string[]
     */
    protected function getCodeceptionSuiteNames(): array
    {
        if (!$this->codeceptionSuiteNames) {
            $this->initCodeceptionInfo();

            $suiteFiles = Finder::create()
                ->in($this->codeceptionInfo['paths']['tests'])
                ->files()
                ->name('*.suite.yml')
                ->name('*.suite.dist.yml')
                ->depth(0);

            foreach ($suiteFiles as $suiteFile) {
                [$suitName] = explode('.', $suiteFile->getBasename());
                $this->codeceptionSuiteNames[] = $suitName;
            }

            $this->codeceptionSuiteNames = array_unique($this->codeceptionSuiteNames);
        }

        return $this->codeceptionSuiteNames;
    }

    /**
     * @param array<string> $suiteNames
     */
    protected function validateArgCodeceptionSuiteNames(array $suiteNames): void
    {
        if (!$suiteNames) {
            return;
        }

        $invalidSuiteNames = array_diff($suiteNames, $this->getCodeceptionSuiteNames());
        if ($invalidSuiteNames) {
            throw new \InvalidArgumentException(
                'The following Codeception suite names are invalid: ' . implode(', ', $invalidSuiteNames),
                1
            );
        }
    }

    protected function getProcessCallback(
        bool $hideStdOutput = false,
        bool $hideStdError = false
    ): \Closure {
        return function (string $type, string $data) use ($hideStdOutput, $hideStdError) {
            if (($type === Process::OUT && $hideStdOutput)
                || ($type === Process::ERR && $hideStdError)
            ) {
                return;
            }

            switch ($type) {
                case Process::OUT:
                    $this->output()->write($data);
                    break;

                case Process::ERR:
                    $this->errorOutput()->write($data);
                    break;
            }
        };
    }

    /**
     * @return array<string, DevPhpExecutable>
     */
    protected function getEnabledPhpExecutables(): array
    {
        /** @var array<string, DevPhpExecutable> $phpExecutables */
        $phpExecutables = array_filter(
            $this->getConfig()->get('php.executables'),
            new ArrayFilterEnabled(),
        );

        return $phpExecutables;
    }
}
