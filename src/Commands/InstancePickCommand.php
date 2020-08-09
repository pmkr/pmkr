<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Pmkr\Pmkr\VariationPickResult\VariationPickCommandResult;
use Pmkr\Pmkr\VariationPickResult\VariationPickResult;
use Pmkr\Pmkr\Util\EnvPathHandler;
use Sweetchuck\Utils\ArrayFilterInterface;
use Sweetchuck\Utils\ComparerInterface;
use Sweetchuck\Utils\Filesystem as UtilsFilesystem;
use Symfony\Component\Console\Input\InputOption;

class InstancePickCommand extends CommandBase
{

    protected EnvPathHandler $envPathHandler;

    protected function initDependencies()
    {
        if ($this->initialized) {
            return $this;
        }

        parent::initDependencies();
        $container = $this->getContainer();
        $this->envPathHandler = $container->get('pmkr.env_path.handler');

        return $this;
    }

    /**
     * Picks an installed instance based on the given $coreVersionConstraints.
     *
     * Instance with the lowest coreVersion number will be chosen.
     *
     * @param string $coreVersionConstraints
     *   https://getcomposer.org/doc/articles/versions.md#writing-version-constraints
     *
     * @command instance:pick
     *
     * @aliases p
     *
     * @option string $threadType
     *   Allowed values: nts, zts.
     * @option bool $highest
     *   By default instance with the lowest coreVersion number will be chosen,
     *   but when this flag is present the highest version will be chosen.
     *
     * @usage '7.4'
     * @usage '>=7.4 <8.1'
     *
     * @pmkrInitNormalizeConfig
     * @pmkrValidateInstanceBinary option.binary
     * @pmkrProcessVariationPickResult
     *
     * @see https://getcomposer.org/doc/articles/versions.md#writing-version-constraints
     */
    public function cmdInstancePickExecute(
        string $coreVersionConstraints,
        array $options = [
            'threadType' => '',
            'binary' => InputOption::VALUE_REQUIRED,
            'highest' => false,
            'format' => 'shell-var-setter',
        ]
    ): VariationPickCommandResult {
        if (preg_match('/^\d+(\.\d+){0,2}$/', $coreVersionConstraints) === 1) {
            // @todo Not sure if this really needed.
            $coreVersionConstraints = ">=$coreVersionConstraints";
        }

        $requirements = ['php' => [$coreVersionConstraints]];

        return $this->getCommandResult($options, $requirements);
    }

    /**
     * Picks an installed instance based on the project requirements.
     *
     * Instance with the lowest coreVersion number will be chosen.
     *
     * @command instance:pick:project
     *
     * @aliases pp
     *
     * @option string $composer
     *   Relative path to the composer.json file from the $projectRoot.
     *   Default value is based on the COMPOSER environment variable.
     * @option bool $noDev
     *   Ignore require-dev dependencies.
     * @option string $threadType
     *   Allowed values: nts, zts.
     * @option bool $highest
     *   By default instance with the lowest coreVersion number will be chosen,
     *   but when this flag is present the highest version will be chosen.
     *
     * @pmkrInitNormalizeConfig
     * @pmkrValidateInstanceBinary option.binary
     * @pmkrProcessVariationPickResult
     *
     * @todo Check the PHP extensions as well.
     */
    public function cmdInstancePickByProject(
        string $projectRoot = '.',
        array $options = [
            'binary' => InputOption::VALUE_REQUIRED,
            'composer' => InputOption::VALUE_REQUIRED,
            'noDev' => false,
            'threadType' => '',
            'highest' => false,
            'format' => 'shell-var-setter',
        ]
    ): VariationPickCommandResult {
        $packages = $this->fetchPackages(
            $projectRoot,
            $options['composer'] ?: (getenv('COMPOSER') ?: 'composer.json'),
            $options['noDev'],
        );
        $requirements = $this->collectRequirements($packages);

        return $this->getCommandResult($options, $requirements);
    }

    /**
     * @hook validate instance:pick:default
     */
    public function cmdInstancePickDefaultValidate(CommandData $commandData)
    {
        $pmkr = $this->getPmkr();
        $defaultVariationKey = $pmkr->defaultVariationKey;
        if (!$defaultVariationKey) {
            throw new \LogicException(
                'The default variation is not defined. It can be done in #/defaultVariationKey',
                1,
            );
        }

        $defaultVariation = $pmkr->defaultVariation;
        if (!$defaultVariation) {
            $app = $this->getContainer()->get('application');
            $appName = $app->getName();

            throw new \LogicException(
                implode(\PHP_EOL, [
                    "The default variation '$defaultVariationKey' is not available.",
                    'Configuration can be validated with the following commands:',
                    "$appName config:validate:schema",
                    "$appName config:validate:integrity",
                    '',
                    'To list the available variations:',
                    "$appName variation:list",
                ]),
                2,
            );
        }
    }

    /**
     * Picks the default variations, which is defined in the
     * `pmkr.*.yml#/defaultVariationKey`.
     *
     * @command instance:pick:default
     *
     * @aliases pd
     *
     * @usage eval "$(pmkr --no-ansi instance:pick:default --format='shell-var-setter')"
     *
     * @option bool $soft
     *   If this flag is true and a pmkr instance is already added to the $PATH
     *   environment variable then it won't be overridden.
     *
     * @option string $binary
     *   Relative path from "<instance_share_dir>/bin" directory.
     *   Default: php
     *
     * @pmkrInitNormalizeConfig
     * @pmkrValidateInstanceBinary option.binary
     * @pmkrProcessVariationPickResult
     */
    public function cmdInstancePickDefaultExecute(
        array $options = [
            'soft' => false,
            'binary' => InputOption::VALUE_REQUIRED,
            'format' => 'shell-var-setter',
        ]
    ): ?VariationPickCommandResult {
        $envPath = (string) $this->getConfig()->get('env.PATH');
        if ($options['soft'] && $this->envPathHandler->getCurrentInstanceName($envPath)) {
            // No need to override the current settings
            // and an instance is already active.
            return null;
        }

        $defaultVariation = $this->getPmkr()->defaultVariation;
        $result = new VariationPickResult();
        $result->instance = $defaultVariation->instance;
        $result->phpRc = $defaultVariation->phpRc;
        $result->phpIniScanDir = $defaultVariation->phpIniScanDir;
        $result->binary = $options['binary'];

        return new VariationPickCommandResult($result, 0);
    }

    /**
     * @command instance:pick:unset
     *
     * @aliases pu
     */
    public function cmdInstancePickUnsetExecute(
        array $options = [
            'format' => 'shell-var-setter',
        ]
    ): VariationPickCommandResult {
        return new VariationPickCommandResult(new VariationPickResult());
    }

    /**
     * @command instance:pick:this
     *
     * @aliases pt
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInitDefaultInstanceName arg.instanceName
     * @pmkrInteractInstanceName
     *   arg.instanceName:
     *     hasShareDir: true
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstanceName arg.instanceName
     * @pmkrValidateInstance
     *   arg.instanceName:
     *     hasShareDir: true
     * @pmkrValidateInstanceBinary
     * @pmkrProcessVariationPickResult
     */
    public function cmdInstancePickThisExecute(
        string $instanceName,
        array $options = [
            'binary' => InputOption::VALUE_REQUIRED,
            'format' => 'shell-var-setter'
        ]
    ): VariationPickCommandResult {
        $result = new VariationPickResult();
        $result->instance = $this->getPmkr()->instances[$instanceName];
        $result->binary = $options['binary'] ?: 'php';

        return new VariationPickCommandResult($result, 0);
    }

    /**
     * @hook process @pmkrProcessVariationPickResult
     */
    public function onProcessVariationPickResult($result, CommandData $commandData)
    {
        if (!($result instanceof CommandResult)) {
            return;
        }

        $pickResult = $result->getOutputData();
        if (!($pickResult instanceof VariationPickResult)) {
            return;
        }

        $format = $commandData->input()->getOption('format');
        if ($format === 'string') {
            $result->setOutputData($pickResult->instance->key);
        }
    }

    protected function getCommandResult(array $options, array $requirements): VariationPickCommandResult
    {
        $instances = $this->getInstances($options, $requirements);
        if (!$instances) {
            $this->logger->error('no available instance');

            return new VariationPickCommandResult(null, 1);
        }

        usort(
            $instances,
            $this->getInstanceComparer($options),
        );

        /** @var \Pmkr\Pmkr\Model\Instance $instance */
        $instance = reset($instances);

        $result = new VariationPickResult();
        $result->instance = $instance;
        $result->binary = $options['binary'];

        return new VariationPickCommandResult($result, 0);
    }

    /**
     * @return \Pmkr\Pmkr\Model\Instance[]
     */
    protected function getInstances(array $options, array $requirements): array
    {
        return array_filter(
            iterator_to_array($this->getPmkr()->instances),
            $this->getInstanceFilter($options, $requirements),
        );
    }

    protected function fetchPackages(
        string $projectRoot,
        string $jsonFileName,
        bool $noDev
    ): array {
        $vendorDir = 'vendor';
        if ($this->fs->exists("$projectRoot/$jsonFileName")) {
            try {
                $json = json_decode(UtilsFilesystem::fileGetContents("$projectRoot/$jsonFileName"), true);
                $vendorDir = $json['config']['vendor-dir'] ?? 'vendor';
            } catch (\RuntimeException $e) {
                // Do nothing.
            }
        }

        $installedFileName = "$projectRoot/$vendorDir/composer/installed.json";
        $packages = $this->fetchPackagesFromInstalledJson($installedFileName, $noDev);

        if ($packages === null) {
            $lockFileName = $this->utils->replaceFileExtension($jsonFileName, 'lock');
            $packages = $this->fetchPackagesFromLock("$projectRoot/$lockFileName", $noDev);
        }

        if ($packages === null) {
            $packages = [];
        }

        $packages['__ROOT__'] = $json ?? [];
        $packages['__ROOT__']['name'] = '__ROOT__';

        return $packages;
    }

    protected function fetchPackagesFromInstalledJson(string $installedFileName, bool $noDev): ?array
    {
        if (!$this->fs->exists($installedFileName)) {
            return null;
        }

        $packages = null;
        try {
            $installed = json_decode(UtilsFilesystem::fileGetContents($installedFileName), true);
            $packages = $installed['packages'] ?? [];
            if ($noDev && !empty($installed['dev'])) {
                $packages = array_diff_assoc(
                    $packages,
                    array_flip($installed['dev-package-names']),
                );
            }
        } catch (\RuntimeException $e) {
            // Do nothing.
        }

        return $packages;
    }

    protected function fetchPackagesFromLock(string $lockFileName, bool $noDev): ?array
    {
        if (!$this->fs->exists($lockFileName)) {
            return null;
        }

        $packages = null;
        try {
            $lock = json_decode(UtilsFilesystem::fileGetContents($lockFileName), true);
            if ($noDev) {
                $packages = $lock['packages'] ?? [];
            } else {
                $packages = ($lock['packages-dev'] ?? []) + ($lock['packages'] ?? []);
            }
        } catch (\RuntimeException $e) {
            // Do nothing.
        }

        return $packages;
    }

    protected function collectRequirements(array $packages): array
    {
        $requirements = [
            'php' => [],
            'extensions' => [],
        ];
        foreach ($packages as $package) {
            $packageName = $package['name'] ?? '';
            foreach ($package['require'] ?? [] as $name => $constraint) {
                if ($name === 'php') {
                    $requirements['php'][$packageName] = $constraint;
                } elseif (preg_match('@^ext-(?P<name>[^/]+)$@', $name, $matches) === 1) {
                    $requirements['extensions'][$matches['name']][$packageName] = $constraint;
                }
            }
        }

        return $requirements;
    }

    protected function getInstanceFilter(array $options, array $requirements): ArrayFilterInterface
    {
        $coreVersionConstraints = implode(' ', array_unique($requirements['php'])) ?: null;
        $isZts = $options['threadType'] ? ($options['threadType'] === 'zts') : null;

        return $this->getContainer()
            ->get('pmkr.instance.filter')
            ->setHasShareDir(true)
            ->setPrimaryCoreVersionConstraints($requirements['php']['__ROOT__'] ?? null)
            ->setCoreVersionConstraints($coreVersionConstraints)
            ->setIsZts($isZts);
    }

    protected function getInstanceComparer(array $options): ComparerInterface
    {
        $direction = $this->utils->boolToCompareDirection($options['highest']);

        return $this->getContainer()
            ->get('array_value.comparer')
            ->setDirection($direction)
            ->setKeys([
                'coreVersion' => [
                    'default' => '0.0.0',
                ],
            ]);
    }
}
