<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\Instance;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Sweetchuck\Utils\Filter\EnabledFilter;

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

    protected function starter(): static
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

    protected function process(): static
    {
        $libraryIds = $this->collectLibraries();
        $envVars = $this->collectParentConfigureEnvVars($libraryIds);
        foreach ($envVars as $name => $value) {
            $this->cmd['envVars'][$name][] = $name . '=' . escapeshellarg($value);
        }

        $this
            ->processCore()
            ->processExtensions();

        return $this;
    }

    protected function processCore(): static
    {
        $this->addCmdEnvVars($this->instance->core->configureEnvVar);
        $this->addCmdOptions($this->instance->core->configure);

        return $this;
    }

    protected function processExtensions(): static
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

    protected function processExtension(Extension $extension): static
    {
        $this->addCmdEnvVars($extension->configureEnvVar);
        $this->addCmdOptions($extension->configure);

        return $this;
    }

    /**
     * @phpstan-return array<string>
     */
    protected function collectLibraries(): array
    {
        $libraryIds = [];

        $osList = $this->instance->core->dependencies['libraries'] ?? [];
        $osId = $this->opSys->pickOpSysIdentifier(array_keys($osList)) ?: 'default';
        $libraryIds += array_filter(
            $osList[$osId] ?? [],
            new EnabledFilter(),
        );

        foreach ($this->instance->extensions as $extension) {
            $osList = $extension->dependencies['libraries'] ?? [];
            $osId = $this->opSys->pickOpSysIdentifier(array_keys($osList)) ?: 'default';
            $libraryIds += array_filter(
                $osList[$osId] ?? [],
                new EnabledFilter(),
            );
        }

        return array_keys($libraryIds);
    }

    /**
     * @phpstan-param array<string> $libraryIds
     *
     * @return array<string, string>
     */
    protected function collectParentConfigureEnvVars(array $libraryIds): array
    {
        $envVarValues = [];
        $libraries = array_intersect_key(
            $this->config->get('libraries') ?: [],
            array_flip($libraryIds),
        );
        foreach ($libraries as $library) {
            if (empty($library['parentConfigureEnvVars'])) {
                continue;
            }

            foreach ($library['parentConfigureEnvVars'] as $envVarName => $osList) {
                /** @phpstan-var array<string> $osList */
                $osId = $this->opSys->pickOpSysIdentifier(array_keys($osList)) ?: 'default';
                $envVarValues[$envVarName] = array_merge(
                    $envVarValues[$envVarName] ?? [],
                    array_values($osList[$osId] ?? []),
                );
            }
        }

        $envVarComparer = new ArrayValueComparer();
        $envVarComparer->setKeys([
            'weight' => [
                'default' => 0,
            ],
        ]);
        $envVars = [];
        /** @phpstan-var string $envVarName */
        foreach (array_keys($envVarValues) as $envVarName) {
            $envVarItems = array_filter($envVarValues[$envVarName], new EnabledFilter());
            usort($envVarItems, $envVarComparer);
            $values = [];
            foreach ($envVarItems as $envVarItem) {
                $values[] = $envVarItem['value'];
            }

            if ($values) {
                $envVars[$envVarName] = implode(\PATH_SEPARATOR, $values);
            }
        }

        return $envVars;
    }
}
