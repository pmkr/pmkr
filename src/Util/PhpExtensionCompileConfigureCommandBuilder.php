<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Model\Extension;

class PhpExtensionCompileConfigureCommandBuilder extends CommandBuilderBase
{

    protected Extension $extension;

    protected string $extensionSrcDir;

    protected string $phpBinDir;

    public function build(
        Extension $extension,
        string $extensionSrcDir,
        string $phpBinDir
    ): string {
        $this->extension = $extension;
        $this->extensionSrcDir = $extensionSrcDir;
        $this->phpBinDir = $phpBinDir;

        return $this->doIt();
    }

    protected function getSrcDir(): string
    {
        return $this->extensionSrcDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function starter()
    {
        $this->cmd['command'][] = './configure';
        $this->addCmdOptions([
            'default' => [
                '--with-php-config' => "{$this->phpBinDir}/php-config",
            ],
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function process()
    {
        $this->addCmdEnvVars($this->extension->configureEnvVar);
        $this->addCmdOptions($this->extension->configure);

        return $this;
    }
}
