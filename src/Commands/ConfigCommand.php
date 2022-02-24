<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use Pmkr\Pmkr\Application;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Util\ConfigNormalizer;
use Pmkr\Pmkr\Util\PmkrConfigValidator;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends CommandBase
{
    protected ConfigNormalizer $pmkrConfigNormalizer;

    protected JsonSchemaValidator $jsonSchemaValidator;

    protected PmkrConfigValidator $pmkrConfigValidator;

    /**
     * {@inheritdoc}
     */
    protected function initDependencies()
    {
        if (!$this->initialized) {
            parent::initDependencies();

            $container = $this->getContainer();
            $this->pmkrConfigNormalizer = $container->get('pmkr.config.normalizer');
            $this->jsonSchemaValidator = $container->get('json_schema.validator');
            $this->pmkrConfigValidator = $container->get('pmkr.config.validator');
        }

        return $this;
    }

    /**
     * Exports PMKR configuration.
     *
     * @param array $options
     * @phpstan-param array<string, mixed> $options
     *
     * @command config:export
     *
     * @option string $format
     *   This is the description of the format option.
     */
    public function cmdConfigExportExecute(
        array $options = [
            'normalize' => false,
            'format' => 'yaml',
        ]
    ): CommandResult {
        $config = $this->getConfig();
        if ($options['normalize']) {
            $this->pmkrConfigNormalizer->normalizeConfig($config);
        }

        return CommandResult::data($config->export());
    }

    /**
     * Check that if the PMKR configuration is valid or not.
     *
     * @command config:validate:schema
     */
    public function cmdConfigValidateExecute(): CommandResult
    {
        $schema = Yaml::parseFile('./schema/pmkr-01.schema.yml');
        $data = json_decode(json_encode($this->getConfig()->export()) ?: '{}');

        $this
            ->jsonSchemaValidator
            ->resolver()
            ->registerRaw(json_encode($schema), $schema['$id']);

        $result = $this
            ->jsonSchemaValidator
            ->setMaxErrors(10)
            ->validate($data, $schema['$id']);

        $data = [];
        $exitCode = 0;

        if ($result->hasError()) {
            $exitCode = 1;
            $data = (new ErrorFormatter())->format($result->error());
        }

        return CommandResult::dataWithExitCode(
            $data,
            $exitCode,
        );
    }

    /**
     * @command config:validate:integrity
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdConfigValidateIntegrityExecute(): CommandResult
    {
        $pmkr = PmkrConfig::__set_state([
            'config' => $this->getConfig(),
            'configPath' => [],
        ]);
        $errors = $this->pmkrConfigValidator->validate($pmkr);

        return CommandResult::dataWithExitCode($errors, count($errors) ? 1 : 0);
    }

    /**
     * Runs: `${EDITOR:-vim} ~/.pmkr/pmkr.*.yml`
     *
     * @command config:edit
     *
     * @pmkrInteractConfigFile arg.configFile
     */
    public function cmdConfigEditExecute(?string $configFile = null): CommandResult
    {
        $appName = Application::NAME;
        $configFileSafe = $configFile === null ?
            escapeshellarg($this->utils->getPmkrHome()) . "/$appName.*.yml"
            : escapeshellarg($configFile);

        $command = sprintf('${EDITOR:-vim} %s', $configFileSafe);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $exitCode = $process->run();

        return CommandResult::exitCode($exitCode);
    }
}
