<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Utils;

abstract class CommandBuilderBase
{
    protected Utils $utils;

    protected OpSys $opSys;

    protected ConfigInterface $config;

    /**
     * @var array{
     *     workingDirectory: string,
     *     envVars: array<string, array<string>>,
     *     envVarsFlat: array<string, string>,
     *     command: array<string>,
     * }
     */
    protected array $cmd = [
        'workingDirectory' => '',
        'envVars' => [],
        'envVarsFlat' => [],
        'command' => [],
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $cmdOptions = [];

    public function __construct(Utils $utils, OpSys $opSys)
    {
        $this->utils = $utils;
        $this->opSys = $opSys;
    }

    protected function doIt(): string
    {
        return $this
            ->init()
            ->starter()
            ->process()
            ->checkCmdOptions()
            ->mergeCmdOptions()
            ->checkCmdEnvVars()
            ->mergeCmdEnvVars()
            ->flat();
    }

    abstract protected function getSrcDir(): string;

    protected function init(): static
    {
        $this->cmdOptions = [];
        $this->cmd = [
            'workingDirectory' => $this->getSrcDir(),
            'envVars' => [],
            'envVarsFlat' => [],
            'command' => [],
        ];

        return $this;
    }

    abstract protected function starter(): static;

    abstract protected function process(): static;

    /**
     * @param array<string, array<string, false|string>> $configureEnvVar
     */
    protected function addCmdEnvVars(array $configureEnvVar): static
    {
        $opSysIdentifier = $this->opSys->pickOpSysIdentifier(array_keys($configureEnvVar));
        $envVars = array_replace(
            $configureEnvVar['default'] ?? [],
            $configureEnvVar[$opSysIdentifier] ?? [],
        );
        foreach ($envVars as $name => $value) {
            if ($value === false) {
                continue;
            }

            $this->cmd['envVars'][$name][] = $name . '=' . escapeshellarg($value);
        }

        return $this;
    }

    /**
     * @param array<string, array<string, null|false|string>> $configure
     */
    protected function addCmdOptions(array $configure): static
    {
        $opSysIdentifier = $this->opSys->pickOpSysIdentifier(array_keys($configure));
        $options = array_replace(
            $configure['default'] ?? [],
            $configure[$opSysIdentifier] ?? [],
        );
        foreach ($options as $name => $value) {
            if ($value === false) {
                continue;
            }

            $this->cmdOptions[$name][] = $name . ($value !== null ? '=' . escapeshellarg($value) : '');
        }

        return $this;
    }

    protected function checkCmdOptions(): static
    {
        foreach ($this->cmdOptions as $name => $values) {
            $occurrences = array_count_values($values);
            if (count($occurrences) > 1) {
                throw new \LogicException(
                    "option $name appears multiple times with different values: " . implode(' ', $values),
                );
            }
        }

        return $this;
    }

    protected function mergeCmdOptions(): static
    {
        foreach ($this->cmdOptions as $value) {
            $this->cmd['command'][] = (string) reset($value);
        }

        return $this;
    }

    protected function checkCmdEnvVars(): static
    {
        foreach ($this->cmd['envVars'] as $name => $values) {
            $occurrences = array_count_values($values);
            if (count($occurrences) > 1) {
                throw new \LogicException(
                    "environment variable $name appears multiple times with different values: " . implode(' ', $values),
                );
            }
        }

        return $this;
    }

    protected function mergeCmdEnvVars(): static
    {
        foreach ($this->cmd['envVars'] as $name => $value) {
            $this->cmd['envVarsFlat'][$name] = (string) reset($value);
        }

        return $this;
    }

    protected function flat(): string
    {
        $flat = implode(" \\\n", $this->cmd['envVarsFlat']);
        if ($flat) {
            $flat .= " \\\n";
        }

        $flat .= $this->cmd['command'][0];
        for ($i = 1; $i < count($this->cmd['command']); $i++) {
            $flat .= " \\\n    " . $this->cmd['command'][$i];
        }

        return $flat;
    }
}
