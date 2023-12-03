<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task;

use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;

abstract class BaseTask extends RoboBaseTask
{
    protected string $taskName = '';

    protected int $taskResultCode = 0;

    protected string $taskResultMessage = '';

    /**
     * @var array<string, mixed>
     */
    protected array $assets = [];

    // region Option - assetNamePrefix.
    protected string $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    public function setAssetNamePrefix(string $value): static
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('assetNamePrefix', $options)) {
            $this->setAssetNamePrefix($options['assetNamePrefix']);
        }

        return $this;
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        if (!$context) {
            $context = [];
        }

        $context['name'] = $this->getTaskName();

        return parent::getTaskContext($context);
    }

    /**
     * @return \Robo\Result<string, mixed>
     */
    public function run()
    {
        return $this
            ->runInit()
            ->runHeader()
            ->runDoIt()
            ->runReturn();
    }

    protected function runInit(): static
    {
        return $this;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'PMKR - @todo {placeholder}',
            [
                'placeholder' => 'real value',
            ],
        );

        return $this;
    }

    abstract protected function runDoIt(): static;

    /**
     * @return \Robo\Result<string, mixed>
     */
    protected function runReturn(): Result
    {
        return new Result(
            $this,
            $this->getTaskResultCode(),
            $this->getTaskResultMessage(),
            $this->getAssetsWithPrefixedNames(),
        );
    }

    protected function getTaskResultCode(): int
    {
        return $this->taskResultCode;
    }

    protected function getTaskResultMessage(): string
    {
        return $this->taskResultMessage;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $assets = [];
        foreach ($this->assets as $key => $value) {
            $assets["{$prefix}{$key}"] = $value;
        }

        return $assets;
    }
}
