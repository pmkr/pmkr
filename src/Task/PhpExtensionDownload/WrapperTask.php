<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionDownload;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Task\BaseTask;

class WrapperTask extends BaseTask implements
    ContainerAwareInterface,
    BuilderAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use TaskLoader;

    protected string $taskName = 'PMKR - PHP extension download wrapper';

    /**
     * @param null|array<string, mixed> $context
     *
     * @return null|array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        if ($extension) {
            $context['extension.key'] = $extension->key;
        }

        return $context;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo('{extension.key}');

        return $this;
    }

    protected ?Instance $instance = null;

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    public function setInstance(Instance $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    protected ?Extension $extension = null;

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    public function setExtension(Extension $extension): static
    {
        $this->extension = $extension;

        return $this;
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

        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        return $this;
    }

    protected function runDoIt(): static
    {
        $extension = $this->getExtension();
        switch ($extension->downloader->type) {
            case 'pecl':
                $task = $this->taskPmkrPhpExtensionDownloadPecl();
                $task
                    ->setInstance($this->getInstance())
                    ->setExtension($extension);
                break;
            case 'git':
                $task = $this->taskPmkrPhpExtensionDownloadGit();
                $task
                    ->setInstance($this->getInstance())
                    ->setExtension($extension);
                break;

            default:
                throw new \Exception('not implemented');
        }

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            $this->taskResultCode = $result->getExitCode() ?: 1;
            $this->taskResultMessage = $result->getMessage();
        }

        return $this;
    }
}
