<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Instance;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Task\BaseTask;
use Symfony\Component\Filesystem\Filesystem;

class DeleteTask extends BaseTask
{
    protected Filesystem $filesystem;

    protected string $taskName = 'PMKR - delete instance';

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $instance = $this->getInstance();
        $context = parent::getTaskContext($context);
        $context += [
            'instanceKey' => $instance ? $instance->key : '__missing_instance_key__',
        ];

        return $context;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'PMKR - delete instance: {instanceKey}',
            $this->getTaskContext(),
        );

        return $this;
    }

    // region instance
    protected ?Instance $instance = null;

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    public function setInstance(?Instance $instance): static
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);

        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        return $this;
    }

    protected function runDoIt(): static
    {
        $instance = $this->getInstance();
        if (!$instance) {
            // @todo Message.
            return $this;
        }

        $dirs = [
            $instance->srcDir,
            $instance->shareDir,
        ];

        foreach ($dirs as $dir) {
            $this->logger->info(
                'delete instance directory: {dir}',
                [
                    'dir' => $dir,
                ],
            );
            $this->filesystem->remove($dir);
        }

        return $this;
    }
}
