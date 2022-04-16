<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\EtcDeploy;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Util\PhpExtensionVersionDetector;
use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment as TwigEnvironment;

class PhpExtensionEtcDeployTask extends BaseEtcDeployTask
{
    protected PhpExtensionVersionDetector $extensionVersionDetector;

    protected ?string $extensionVersion;

    protected string $taskName = 'PMKR - Deploy extension etc files';

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        $context += [
            'extensionKey' => $extension ? $extension->key : '__missing_extension_key__',
            'extensionName' => $extension ? $extension->name : '__missing_extension_name__',
        ];

        return $context;
    }

    // region extension
    protected Extension $extension;

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    /**
     * @return $this
     */
    public function setExtension(?Extension $extension)
    {
        $this->extension = $extension;

        return $this;
    }
    # endregion

    public function __construct(
        PhpExtensionVersionDetector $extensionVersionDetector,
        TwigEnvironment $twig,
        Filesystem $filesystem
    ) {
        parent::__construct($twig, $filesystem);
        $this->extensionVersionDetector = $extensionVersionDetector;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo(
            'extensionKey is: {extensionKey}',
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $instance = $this->getInstance();
        $instanceSrcDir = $instance->srcDir;
        $extension = $this->getExtension();
        $extensionName = $extension->name;
        $extensionSrcDir = "$instanceSrcDir/ext/$extensionName";

        $this->extensionVersion = $this->extensionVersionDetector->detect(
            $instance->coreVersionNumber,
            $extensionSrcDir,
            $extensionName,
        );
        $isCoreExtension = $this->extensionVersion === 'PHP_VERSION';
        if ($isCoreExtension) {
            $this->extensionVersion = $instance->coreVersion;
        }

        $etc = $extension->etc;
        foreach ($etc['files'] ?? [] as $fileDefKey => $fileDef) {
            $this->deploy($etc, $fileDefKey, $fileDef);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getVars(array $etc, string $fileDefKey): array
    {
        $instance = $this->getInstance();
        $extension = $this->getExtension();
        $extensionSet = $instance->extensionSet;
        $extensionSetItem = $extensionSet[$extension->key] ?? null;

        $coreVersionNumber = VersionNumber::createFromString($instance->coreVersion);

        $config = $this->getConfig();

        $default = [
            'extFileSuffix' => $this->getExtFileSuffix($instance),
            'dir' => $config->get('dir'),
            'env' => $config->get('env'),
            'instance' => [
                'key' => $instance->key,
                'shareDir' => $instance->shareDir,
                'sessionsDir' => $config->get('dir.run') . '/pmkr-php--session',
            ],
            'core' => [
                'name' => $instance->coreName,
                'version' => $instance->coreVersion,
                'versionMA2' => $coreVersionNumber->format(VersionNumber::FORMAT_MA2),
                'versionMA2MI2' => $coreVersionNumber->format(VersionNumber::FORMAT_MA2MI2),
            ],
            'extension' => [
                'key' => $extension->key,
                'name' => $extension->name,
                'version' => $this->extensionVersion,
                'versionMA2' => null,
                'versionMA2MI2' => null,
            ],
            'extensionSetItem' => [
                'status' => $extensionSetItem ? $extensionSetItem->status : 'optional',
                'isEnabled' => $extensionSetItem ? $extensionSetItem->isEnabled : false,
            ],
        ];

        if ($this->extensionVersion !== null) {
            $extensionVersionNumber = VersionNumber::createFromString($this->extensionVersion);
            $default['extension']['versionMA2'] = $extensionVersionNumber->format(VersionNumber::FORMAT_MA2);
            $default['extension']['versionMA2MI2'] = $extensionVersionNumber->format(VersionNumber::FORMAT_MA2MI2);
        }

        return array_replace_recursive(
            $default,
            $this->getVarsUname(),
            $etc['vars'] ?? [],
            $etc['files'][$fileDefKey]['vars'] ?? [],
        );
    }
}
