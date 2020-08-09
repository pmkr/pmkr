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

    protected array $cmd = [];

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

    protected function init()
    {
        $this->cmdOptions = [];
        $this->cmd = [
            'workingDirectory' => $this->getSrcDir(),
            'envVars' => [],
            'command' => [],
        ];

        return $this;
    }

    /**
     * @return $this
     */
    abstract protected function starter();

    /**
     * @return $this
     */
    abstract protected function process();

    protected function addCmdEnvVars(array $configureEnvVar)
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

    protected function addCmdOptions(array $configure)
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

    /**
     * @return $this
     */
    protected function checkCmdOptions()
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

    /**
     * @return $this
     */
    protected function mergeCmdOptions()
    {
        foreach ($this->cmdOptions as $value) {
            $this->cmd['command'][] = reset($value);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function checkCmdEnvVars()
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

    /**
     * @return $this
     */
    protected function mergeCmdEnvVars()
    {
        foreach ($this->cmd['envVars'] as $name => $value) {
            $this->cmd['envVars'][$name] = reset($value);
        }

        return $this;
    }

    protected function flat(): string
    {
        $flat = implode(" \\\n", $this->cmd['envVars']);
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
