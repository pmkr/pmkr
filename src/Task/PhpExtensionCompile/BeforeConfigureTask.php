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
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        $context['extensionKey'] = $extension ? $extension->key : '__missing_extension_key__';

        return $context;
    }

    protected function runHeader(): static
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

    public function setExtensionSrcDir(string $extensionSrcDir): static
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

    public function setExtension(Extension $extension): static
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
