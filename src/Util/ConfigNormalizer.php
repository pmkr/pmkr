<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigInterface;

class ConfigNormalizer
{

    /**
     * @return $this
     */
    public function normalizeConfig(ConfigInterface $config)
    {
        $this
            ->normalizeConfigDirs($config)
            ->normalizeConfigPatches($config)
            ->normalizeConfigLibraries($config)
            ->normalizeConfigExtensions($config)
            ->normalizeConfigExtensionSets($config)
            ->normalizeConfigCores($config)
            ->normalizeConfigInstances($config)
            ->normalizeConfigVariations($config)
            ->normalizeConfigSyntaxHighlighter($config);

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigDirs(ConfigInterface $config)
    {
        $tmpDir = $config->get('dir.tmp');
        if ($tmpDir === null) {
            $config->setDefault('dir.tmp', sys_get_temp_dir());
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigPatches(ConfigInterface $config)
    {
        $patches = (array) $config->get('patches');
        foreach ($patches as $patchKey => $patch) {
            $config->setDefault("patches.$patchKey.key", $patchKey);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigLibraries(ConfigInterface $config)
    {
        $libraries = (array) $config->get('libraries');
        foreach ($libraries as $libKey => $lib) {
            $config->setDefault("libraries.$libKey.key", $libKey);
            foreach ($lib['parentConfigureEnvVars'] ?? [] as $envVarName => $osList) {
                foreach ($osList as $osId => $items) {
                    foreach ($items as $itemId => $item) {
                        $configKeyPrefix = "libraries.$libKey.parentConfigureEnvVars.$envVarName.$osId.$itemId";

                        if (!array_key_exists('enabled', $item)) {
                            $config->setDefault("$configKeyPrefix.enabled", true);
                        }

                        if (!array_key_exists('weight', $item)) {
                            $config->setDefault("$configKeyPrefix.weight", 0);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigExtensions(ConfigInterface $config)
    {
        $extensions = (array) $config->get('extensions');
        foreach ($extensions as $key => $info) {
            $config->setDefault("extensions.$key.key", $key);

            if (!array_key_exists('name', $info)) {
                [$name] = explode('-', $key, 2);
                $config->setDefault("extensions.$key.name", $name);
            }

            if (!array_key_exists('ignore', $info)) {
                $config->setDefault("extensions.$key.ignore", 'never');
            }

            if (!array_key_exists('version', $info)) {
                $config->setDefault("extensions.$key.version", 'stable');
            }
        }

        $extensionSets = $config->get('extensionSets');
        if ($extensionSets === null) {
            $config->setDefault('extensionSets', []);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigExtensionSets(ConfigInterface $config)
    {
        $extensionSets = (array) $config->get('extensionSets');
        foreach ($extensionSets as $extSetKey => $extensionSet) {
            foreach ($extensionSet as $extSetItemKey => $item) {
                $config->setDefault("extensionSets.$extSetKey.$extSetItemKey.key", $extSetItemKey);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigCores(ConfigInterface $config)
    {
        $cores = $config->get('cores');
        if ($cores === null) {
            $config->setDefault('cores', []);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigInstances(ConfigInterface $config)
    {
        $instances = $config->get('instances');
        if ($instances === null) {
            $config->setDefault('instances', []);
        }

        settype($instances, 'array');
        foreach (array_keys($instances) as $instanceName) {
            $config->setDefault("instances.$instanceName.key", $instanceName);
            $value = $config->get("instances.$instanceName.coreNameSuffix");
            if ($value === null) {
                $config->setDefault("instances.$instanceName.coreNameSuffix", '');
            }

            $value = $config->get("instances.$instanceName.extensionSetNameSuffix");
            if ($value === null) {
                $config->setDefault("instances.$instanceName.extensionSetNameSuffix", '');
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigVariations(ConfigInterface $config)
    {
        $variations = $config->get('variations');
        if ($variations === null) {
            $config->setDefault('variations', []);
        }

        settype($variations, 'array');
        foreach (array_keys($variations) as $variationKey) {
            $config->setDefault("variations.$variationKey.key", $variationKey);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function normalizeConfigSyntaxHighlighter(ConfigInterface $config)
    {
        $topKey = 'syntaxHighlighter.languageMapping';
        $languageMapping = $config->get($topKey) ?: [];
        foreach ($languageMapping as $outputFormat => $languages) {
            foreach ($languages as $lang => $handlers) {
                foreach ($handlers as $handlerName => $handler) {
                    $config->setDefault("$topKey.$outputFormat.$lang.$handlerName.handlerName", $handlerName);
                }
            }
        }

        return $this;
    }
}
