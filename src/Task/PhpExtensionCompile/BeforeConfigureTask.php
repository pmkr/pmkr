<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionCompile;

use Robo\Contract\BuilderAwareInterface;
use Robo\Task\Base\Tasks as ExecTaskLoader;
use Robo\TaskAccessor;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Task\BaseTask;

class BeforeConfigureTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use ExecTaskLoader;

    protected string $taskName = 'PMKR extension:compile:run:before';

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        $context['extensionKey'] = $extension ? $extension->key : '__missing_extension_key__';

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo('{extensionKey}');

        return $this;
    }

    // region extensionSrcDir
    protected string $extensionSrcDir = '';

    public function getExtensionSrcDir(): string
    {
        return $this->extensionSrcDir;
    }

    /**
     * @return $this
     */
    public function setExtensionSrcDir(string $extensionSrcDir)
    {
        $this->extensionSrcDir = $extensionSrcDir;

        return $this;
    }
    // endregion

    // region extension
    protected ?Extension $extension = null;

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    /**
     * @return $this
     */
    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;

        return $this;
    }
    // endregion

    // region phpBinDir
    protected string $phpBinDir = '';

    public function getPhpBinDir(): string
    {
        return $this->phpBinDir;
    }

    /**
     * @return $this
     */
    public function setPhpBinDir(string $phpBinDir)
    {
        $this->phpBinDir = $phpBinDir;

        return $this;
    }
    // endregion

    public function setOptions(array $options)
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

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $extension = $this->getExtension();
        $script = $extension->compiler->options['before'] ?? null;
        if (!$script) {
            $this->logger->info('There is no before script');

            return $this;
        }

        $result = $this
            ->taskExecStack()
            ->dir($this->getExtensionSrcDir())
            ->envVars([
                'extensionName' => $extension->name,
                'phpBinDir' => $this->getPhpBinDir(),
            ])
            ->exec($extension->compiler->options['before'])
            ->run();

        if (!$result->wasSuccessful()) {
            throw new \Exception($result->getMessage(), $result->getExitCode());
        }

        return $this;
    }
}
