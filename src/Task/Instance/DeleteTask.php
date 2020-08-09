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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
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

    /**
     * @return $this
     */
    public function setInstance(?Instance $instance)
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        return $this;
    }

    protected function runDoIt()
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
