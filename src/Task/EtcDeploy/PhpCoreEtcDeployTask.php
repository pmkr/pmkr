<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\EtcDeploy;

use Sweetchuck\Utils\VersionNumber;

class PhpCoreEtcDeployTask extends BaseEtcDeployTask
{
    protected string $taskName = 'PMKR - Deploy core etc files {instanceKey}';

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
            'PMKR - deploy core etc files: {instanceKey}',
        );

        return $this;
    }

    protected function runDoIt()
    {
        $instance = $this->getInstance();
        $etc = $instance->core->etc;
        foreach ($etc['files'] ?? [] as $fileDefKey => $fileDef) {
            $this->deploy($etc, $fileDefKey, $fileDef);
        }

        // @todo Make it configurable which one is the default.
        $etcDir = "$instance->shareDir/etc";
        if (!$this->filesystem->exists("$etcDir/php.ini")) {
            if ($this->filesystem->exists("$etcDir/php.xdebug.ini")) {
                $this->filesystem->symlink(
                    "./php.xdebug.ini",
                    "$etcDir/php.ini",
                );
            }
        }

        return $this;
    }

    protected function getVars(array $etc, string $fileDefKey): array
    {
        $instance = $this->getInstance();
        $coreVersionNumber = VersionNumber::createFromString($instance->coreVersion);
        $config = $this->getConfig();

        $default = [
            'dir' => $config->get('dir'),
            'env' => $config->get('env'),
            'instance' => [
                'key' => $instance->key,
                'shareDir' => $instance->shareDir,
            ],
            'core' => [
                'name' => $instance->coreName,
                'version' => $instance->coreVersion,
                'versionMA2' => $coreVersionNumber->format(VersionNumber::FORMAT_MA2),
                'versionMA2MI2' => $coreVersionNumber->format(VersionNumber::FORMAT_MA2MI2),
            ],
            'extensions' => [],
        ];

        foreach ($instance->extensionSet as $extensionKey => $extensionSetItem) {
            $default['extensions'][$extensionSetItem->extensionName] = [
                'key' => $extensionSetItem->key,
                'name' => $extensionSetItem->extensionName,
                'status' => $extensionSetItem->status,
                'isEnabled' => $extensionSetItem->isEnabled,
            ];
        }

        return array_replace_recursive(
            $default,
            $this->getVarsUname(),
            $etc['vars'] ?? [],
            $etc['files'][$fileDefKey]['vars'] ?? [],
        );
    }
}
