<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionCompile;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Task\BaseTask;

class WrapperTask extends BaseTask implements
    ContainerAwareInterface,
    BuilderAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use TaskLoader;

    protected string $taskName = 'PMKR - PHP extension compile';

    // region extensionSrcDir
    protected string $extensionSrcDir = '';

    public function getExtensionSrcDir(): string
    {
        return $this->extensionSrcDir;
    }

    public function setExtensionSrcDir(string $extensionSrcDir): static
    {
        $this->extensionSrcDir = $extensionSrcDir;

        return $this;
    }
    // endregion

    //region extension
    protected ?Extension $extension = null;

    public function getExtension(): Extension
    {
        return $this->extension;
    }

    public function setExtension(Extension $extension): static
    {
        $this->extension = $extension;

        return $this;
    }
    //endregion

    // region phpBinDir
    protected string $phpBinDir = '';

    public function getPhpBinDir(): string
    {
        return $this->phpBinDir;
    }

    public function setPhpBinDir(string $phpBinDir): static
    {
        $this->phpBinDir = $phpBinDir;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);

        if (array_key_exists('extensionSrcDir', $options)) {
            $this->setExtensionSrcDir($options['extensionSrcDir']);
        }

        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        if (array_key_exists('phpBinDir', $options)) {
            $this->setPhpBinDir($options['phpBinDir']);
        }

        return $this;
    }

    protected function runDoIt(): static
    {
        $extension = $this->getExtension();
        switch ($extension->compiler->type) {
            case 'pecl':
                $task = $this->taskPmkrPhpExtensionCompilePecl();
                $task
                    ->setExtension($extension)
                    ->setExtensionSrcDir($this->getExtensionSrcDir())
                    ->setPhpBinDir($this->getPhpBinDir());
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
