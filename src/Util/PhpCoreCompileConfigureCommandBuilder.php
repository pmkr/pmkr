<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\Instance;

/**
 * @todo Support environment variables.
 */
class PhpCoreCompileConfigureCommandBuilder extends CommandBuilderBase
{
    protected Instance $instance;

    public function build(Instance $instance): string
    {
        $this->config = $instance->getConfig();
        $this->instance = $instance;

        return $this->doIt();
    }

    protected function getSrcDir(): string
    {
        return $this->instance->srcDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function starter()
    {
        $instanceShareDir = $this->instance->shareDir;

        $this->cmd['command'][] = './configure';
        $this->addCmdOptions([
            'default' => [
                '--prefix' => $instanceShareDir,
                '--with-config-file-path' => "$instanceShareDir/etc",
                '--with-config-file-scan-dir' => "$instanceShareDir/etc/conf/default",
                '--disable-all' => null,
            ],
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function process()
    {
        $this
            ->processCore()
            ->processExtensions();

        return $this;
    }

    /**
     * @return $this
     */
    protected function processCore()
    {
        $this->addCmdEnvVars($this->instance->core->configureEnvVar);
        $this->addCmdOptions($this->instance->core->configure);

        return $this;
    }

    /**
     * @return $this
     */
    protected function processExtensions()
    {
        $threadType = $this->instance->threadType;
        /**
         * @var string $extRef
         * @var \Pmkr\Pmkr\Model\ExtensionSetItem $extensionSetItem
         */
        foreach ($this->instance->extensionSet as $extRef => $extensionSetItem) {
            $extension = $this->instance->extensions[$extRef];
            if ($extensionSetItem->status !== 'enabled'
                || $this->utils->isIgnoredExtension($threadType, $extension)
            ) {
                continue;
            }

            $this->processExtension($extension);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function processExtension(Extension $extension)
    {
        $this->addCmdEnvVars($extension->configureEnvVar);
        $this->addCmdOptions($extension->configure);

        return $this;
    }
}
