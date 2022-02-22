<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Pmkr\Pmkr\Util\ConfigFileCollector;
use Pmkr\Pmkr\Util\ConfigNormalizer;
use Pmkr\Pmkr\Util\EnvPathHandler;
use Sweetchuck\Utils\Filesystem as UtilsFilesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class BaseHookCommand extends CommandBase
{

    protected ConfigNormalizer $configNormalizer;

    protected EnvPathHandler $envPathHandler;

    protected ConfigFileCollector $configFileCollector;

    /**
     * {@inheritdoc}
     */
    protected function initDependencies()
    {
        if (!$this->initialized) {
            parent::initDependencies();
            $container = $this->getContainer();
            $this->configNormalizer = $container->get('pmkr.config.normalizer');
            $this->envPathHandler = $container->get('pmkr.env_path.handler');
            $this->configFileCollector = $container->get('pmkr.config_file.collector');
        }

        return $this;
    }

    /**
     * @hook init @pmkrInitNormalizeConfig
     */
    public function onHookInitPmkrNormalizeConfig(): void
    {
        $this->initDependencies();
        $this->configNormalizer->normalizeConfig($this->getConfig());
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook init @pmkrInitCurrentInstanceName
     */
    public function onHookInitCurrentInstanceName(
        InputInterface $input,
        AnnotationData $annotationData
    ): void {
        $currentInstanceName = $this->envPathHandler->getCurrentInstanceName((string) getenv('PATH'));
        if ($currentInstanceName === null) {
            return;
        }

        $inputLocators = $this->parseMultiValueAnnotation('pmkrInitDefaultInstanceName', $annotationData);
        foreach ($inputLocators as $inputLocator) {
            $instanceName = $this->utils->getInputValue($input, $inputLocator);
            if ($instanceName !== null) {
                continue;
            }

            $this->utils->setInputValue($input, $inputLocator, $currentInstanceName);
        }
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook init @pmkrInitDefaultInstanceName
     *
     * @link https://github.com/consolidation/annotated-command#initialize-hook
     */
    public function onHookInitPmkrDefaultInstanceName(
        InputInterface $input,
        AnnotationData $annotationData
    ): void {
        $defaultInstanceName = $this->getConfig()->get('defaultInstanceName');
        if ($defaultInstanceName === null) {
            return;
        }

        $inputLocators = $this->parseMultiValueAnnotation('pmkrInitDefaultInstanceName', $annotationData);
        foreach ($inputLocators as $inputLocator) {
            $instanceName = $this->utils->getInputValue($input, $inputLocator);
            if ($instanceName !== null) {
                continue;
            }

            $this->utils->setInputValue($input, $inputLocator, $defaultInstanceName);
        }
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook interact @pmkrInteractInstanceName
     *
     * @see \Pmkr\Pmkr\Util\Filter\InstanceFilter::setOptions
     */
    public function onHookInteractInstanceName(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        if (!$input->isInteractive()) {
            return;
        }

        $tag = 'pmkrInteractInstanceName';
        $inputLocators = $this->getInputLocatorsWithYaml($tag, $annotationData);
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $pmkr = $this->getPmkr();
        $instances = iterator_to_array($pmkr->instances->getIterator());
        $io = new SymfonyStyle($input, $output);
        $trueFilter = function () {
            return true;
        };
        $container = $this->getContainer();
        foreach ($inputLocators as $inputLocator => $filterOptions) {
            $instanceName = $this->utils->getInputValue($input, $inputLocator);
            if ($instanceName !== null) {
                continue;
            }

            $filter = $trueFilter;
            if ($filterOptions) {
                $filter = $container->get('pmkr.instance.filter');
                $filter->setOptions($filterOptions);
            }

            $availableInstances = array_filter($instances, $filter);
            if ($availableInstances) {
                $instanceName = $io->choice(
                    "$inputLocator: Choice an instance name",
                    $this->utils->ioInstanceOptions($availableInstances),
                );
                $this->utils->setInputValue($input, $inputLocator, $instanceName);
            }
        }
    }

    /**
     * @hook validate @pmkrValidateResolveInstanceAlias
     */
    public function onHookValidateResolveInstanceAlias(CommandData $commandData): void
    {
        $inputLocators = $this->parseMultiValueAnnotation(
            'pmkrValidateResolveInstanceAlias',
            $commandData->annotationData(),
        );

        $instances = $this->getConfig()->get('instances') ?: [];
        $aliases = $this->getConfig()->get('aliases') ?: [];
        $input = $commandData->input();
        foreach ($inputLocators as $inputLocator) {
            $instanceAlias = $this->utils->getInputValue($input, $inputLocator);
            if (isset($instances[$instanceAlias])
                || !isset($aliases[$instanceAlias])
            ) {
                // No need to resolve, it is already a valid instanceName,
                // or invalid alias.
                continue;
            }

            $instanceKey = $aliases[$instanceAlias];
            $this->logger->notice(
                'Instance alias {alias} was resolved as {instance.key} for input {locator}',
                [
                    'alias' => $instanceAlias,
                    'instance.key' => $instanceKey,
                    'locator' => $inputLocator,
                ],
            );

            $this->utils->setInputValue($input, $inputLocator, $instanceKey);
        }
    }

    /**
     * @hook validate @pmkrValidateInstanceName
     *
     * @todo Support for InstanceFilter options like in pmkrInteractInstanceName.
     */
    public function onHookValidateInstanceName(CommandData $commandData): void
    {
        $tag = 'pmkrValidateInstanceName';
        $inputLocators = $this->getInputLocatorsWithYaml($tag, $commandData->annotationData());
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $input = $commandData->input();
        $pmkr = $this->getPmkr();
        $instances = iterator_to_array($pmkr->instances->getIterator());
        $trueFilter = function () {
            return true;
        };
        $container = $this->getContainer();
        $errorMessages = [];
        foreach ($inputLocators as $inputLocator => $filterOptions) {
            $instanceName = $this->utils->getInputValue($input, $inputLocator);

            $filter = $trueFilter;
            if ($filterOptions) {
                $filter = $container->get('pmkr.instance.filter');
                $filter->setOptions($filterOptions);
            }

            $availableInstances = array_filter($instances, $filter);
            if (!isset($availableInstances[$instanceName])) {
                // @todo Add reason to the error message.
                // @todo Other error message when there is no available instance.
                $errorMessages[] = strtr(
                    'Instance name "{name}" is invalid for "{locator}". Valid instance names are: {names}',
                    [
                        '{name}' => $instanceName,
                        '{locator}' => $inputLocator,
                        '{names}' => implode(', ', array_keys($availableInstances)),
                    ],
                );
            }
        }

        if (!$errorMessages) {
            return;
        }

        throw new \Exception(implode(\PHP_EOL, $errorMessages));
    }

    /**
     * @hook validate @pmkrValidateInstance
     */
    public function onHookValidateInstance(CommandData $commandData): void
    {
        $tag = 'pmkrValidateInstance';
        $inputLocators = $this->getInputLocatorsWithYaml($tag, $commandData->annotationData());
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $pmkr = $this->getPmkr();
        $input = $commandData->input();
        $message = [];
        foreach ($inputLocators as $inputLocator => $filterOptions) {
            $instanceName = $this->utils->getInputValue($input, $inputLocator);
            $instance = $pmkr->instances[$instanceName];
            $filter = $this->getContainer()->get('pmkr.instance.filter');
            $filter->setOptions($filterOptions);
            if (!$filter->check($instance)) {
                $message[] = "Instance {$instance->key} is not available for this operation.";
            }
        }

        if (!$message) {
            return;
        }

        $message[] = 'Missing directories can be created with the following command: `pmkr instance:install`';

        throw new \Exception(implode(\PHP_EOL, $message));
    }

    /**
     * @hook validate @pmkrValidateInstanceBinary
     *
     * @link https://github.com/consolidation/annotated-command#validate-hook
     */
    public function onHookValidatePmkrValidateInstanceBinary(CommandData $commandData): void
    {
        $tag = 'pmkrValidateInstanceBinary';
        $inputLocators = $this->getInputLocatorsWithYaml($tag, $commandData->annotationData());
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $input = $commandData->input();
        $errorMessages = [];
        foreach ($inputLocators as $inputLocator => $filterOptions) {
            $binary = $this->utils->getInputValue($input, $inputLocator);
            if (!$binary) {
                continue;
            }

            $errors = $this->utils->validateInstanceBinary($binary);
            if (!$errors) {
                continue;
            }

            $errorMessages[] = "$inputLocator is invalid. " . implode(' ', $errors);
        }

        if (!$errorMessages) {
            return;
        }

        throw new \Exception(implode(\PHP_EOL, $errorMessages));
    }

    /**
     * @hook validate @pmkrNormalizeCommaSeparatedList
     */
    public function onHookValidateNormalizeCommaSeparatedList(CommandData $commandData): void
    {
        $inputLocators = $this->parseMultiValueAnnotation(
            'pmkrNormalizeCommaSeparatedList',
            $commandData->annotationData(),
        );
        $input = $commandData->input();
        foreach ($inputLocators as $inputLocator) {
            $value = $this->utils->getInputValue($input, $inputLocator);
            $this->utils->setInputValue(
                $input,
                $inputLocator,
                $this->utils->normalizeCommaSeparatedList($value),
            );
        }
    }

    /**
     * @hook validate @pmkrValidateVariationKey
     */
    public function onHookValidateVariationKey(CommandData $commandData): void
    {
        $tag = 'pmkrValidateVariationKey';
        $inputLocators = $this->getInputLocatorsWithYaml($tag, $commandData->annotationData());
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $input = $commandData->input();
        $pmkr = $this->getPmkr();
        $variations = iterator_to_array($pmkr->variations->getIterator());
        $trueFilter = function () {
            return true;
        };
        $errorMessages = [];
        foreach ($inputLocators as $inputLocator => $filterOptions) {
            $variationKey = $this->utils->getInputValue($input, $inputLocator);

            $filter = $trueFilter;
            $availableVariations = array_filter($variations, $filter);
            if (!isset($availableVariations[$variationKey])) {
                // @todo Add reason to the error message.
                // @todo Other error message when there is no available instance.
                $errorMessages[] = strtr(
                    'Variation key "{name}" is invalid for "{locator}". Valid variation keys are: {names}',
                    [
                        '{name}' => $variationKey,
                        '{locator}' => $inputLocator,
                        '{names}' => implode(', ', array_keys($availableVariations)),
                    ],
                );
            }
        }

        if (!$errorMessages) {
            return;
        }

        throw new \Exception(implode(\PHP_EOL, $errorMessages));
    }

    /**
     * @hook validate @pmkrShellFileDescriptor
     */
    public function onHookValidateShellFileDescriptor(CommandData $commandData): void
    {
        $inputLocators = $this->parseMultiValueAnnotation(
            'pmkrShellFileDescriptor',
            $commandData->annotationData(),
        );
        $input = $commandData->input();
        foreach ($inputLocators as $inputLocator) {
            $value = $this->utils->getInputValue($input, $inputLocator);
            $this->utils->setInputValue(
                $input,
                $inputLocator,
                UtilsFilesystem::normalizeShellFileDescriptor($value),
            );
        }
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @hook interact @pmkrInteractConfigFile
     */
    public function onHookInteractPmkrConfigFiles(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $tag = 'pmkrInteractConfigFile';
        $inputLocators = $this->parseMultiValueAnnotation($tag, $annotationData);
        assert(count($inputLocators) > 0, "@$tag requires at least one input locator.");

        $choices = $this->configFileCollector->collectAsChoices();
        if (!$choices) {
            $this->logger->warning('There are no config files. Run: `pmkr init`');

            return;
        }

        $allOfThem = '- All -';
        array_unshift($choices, $allOfThem);
        $io = new SymfonyStyle($input, $output);
        foreach ($inputLocators as $inputLocator) {
            $configFileName = $this->utils->getInputValue($input, $inputLocator);
            if ($configFileName !== null) {
                continue;
            }

            $configFileName = $io->choice(
                "$inputLocator: Choice a config file",
                $choices,
            );

            if ($configFileName === $allOfThem) {
                continue;
            }

            $this->utils->setInputValue($input, $inputLocator, $configFileName);
        }
    }

    /**
     * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
     *
     * @return array<string, mixed>
     */
    protected function getInputLocatorsWithYaml(string $tag, AnnotationData $annotationData): array
    {
        $tagValue = $annotationData->get($tag);
        if (strpos($tagValue, "\n") === false) {
            return array_fill_keys(
                $this->parseMultiValueAnnotation($tag, $annotationData),
                [],
            );
        }

        return Yaml::parse('top-level:' . $tagValue)['top-level'];
    }
}
