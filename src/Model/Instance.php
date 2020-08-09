<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

use Pmkr\Pmkr\Application;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Utils\VersionNumber;

/**
 * @property-read string $key
 * @property-read bool $hidden
 * @property-read string $description
 * @property-read bool $isZts
 * @property-read string $threadType
 * @property-read string $coreVersion
 * @property-read \Sweetchuck\Utils\VersionNumber $coreVersionNumber
 * @property-read \Pmkr\Pmkr\Model\Checksum $coreChecksum
 * @property-read string $coreNameSuffix
 * @property-read null|string $coreName
 * @property-read null|\Pmkr\Pmkr\Model\Core $core
 * @property-read null|string $extensionSetNameSuffix
 * @property-read null|string $extensionSetName
 * @property-read \Pmkr\Pmkr\Model\ExtensionSet $extensionSet
 * @property-read \Pmkr\Pmkr\Model\Extension[] $extensions
 * @property-read string $srcDir
 * @property-read string $shareDir
 * @property-read string $sessionsDir
 */
class Instance extends Base
{
    protected Utils $utils;

    public static function __set_state($values)
    {
        $self = parent::__set_state($values);
        $self->utils = new Utils($self->getConfig());

        return $self;
    }

    protected array $propertyMapping = [
        'key' => [],
        'hidden' => [
            'default' => false,
        ],
        'description' => [],
        'isZts' => [
            'default' => false,
        ],
        'threadType' => [
            'type' => 'callback',
            'callback' => 'threadType',
        ],
        'coreVersion' => [],
        'coreVersionNumber' => [
            'type' => 'callback',
            'callback' => 'coreVersionNumber',
        ],
        'coreChecksum' => [
            'type' => Checksum::class,
        ],
        'coreNameSuffix' => [],
        'coreName' => [
            'type' => 'callback',
            'callback' => 'coreName',
        ],
        'core' => [
            'type' => 'callback',
            'callback' => 'core',
        ],
        'extensionSetNameSuffix' => [],
        'extensionSetName' => [
            'type' => 'callback',
            'callback' => 'extensionSetName',
        ],
        'extensionSet' => [
            'type' => 'callback',
            'callback' => 'extensionSet',
        ],
        'extensions' => [
            'type' => 'callback',
            'callback' => 'extensions',
        ],
        'srcDir' => [
            'type' => 'callback',
            'callback' => 'srcDir',
        ],
        'shareDir' => [
            'type' => 'callback',
            'callback' => 'shareDir',
        ],
        'sessionsDir' => [
            'type' => 'callback',
            'callback' => 'sessionsDir',
        ],
    ];

    protected function threadType(): string
    {
        return $this->isZts ? 'zts' : 'nts';
    }

    protected function coreVersionNumber(): VersionNumber
    {
        return VersionNumber::createFromString($this->coreVersion);
    }

    protected function coreName(): ?string
    {
        $cores = $this->getConfig()->get('cores') ?: [];
        $coreNameCandidates = $this->utils->nameCandidates(
            '',
            $this->coreVersionNumber,
            $this->isZts,
            $this->coreNameSuffix ?? '',
        );

        return $this->utils->findCandidate($coreNameCandidates, $cores);
    }

    protected function core(): ?Core
    {
        $config = $this->getConfig();
        $coreName = $this->coreName;
        if (!$config->has("cores.$coreName")) {
            return null;
        }

        return Core::__set_state([
            'config' => $config,
            'configPath' => ['cores', $this->coreName],
        ]);
    }

    protected function srcDir(): string
    {
        $parent = $this->getConfig()->get('dir.src');
        $prefix = Application::INSTANCE_DIR_PREFIX;

        return "$parent/$prefix-$this->key";
    }

    protected function shareDir(): string
    {
        $parent = $this->getConfig()->get('dir.share');
        $prefix = Application::INSTANCE_DIR_PREFIX;

        return "$parent/$prefix-$this->key";
    }

    protected function sessionsDir(): string
    {
        $parent = $this->getConfig()->get('dir.run');
        $prefix = Application::INSTANCE_DIR_PREFIX;

        return "$parent/$prefix-$this->key-sessions";
    }

    protected function extensionSetName(): ?string
    {
        $extensionSets = $this->getConfig()->get('extensionSets');
        $extensionSetNameCandidates = $this->utils->nameCandidates(
            '',
            $this->coreVersionNumber,
            $this->isZts,
            $this->extensionSetNameSuffix ?? '',
        );

        return $this->utils->findCandidate(
            $extensionSetNameCandidates,
            $extensionSets,
        );
    }

    protected function extensionSet(): ?ExtensionSet
    {
        $extensionSetName = $this->extensionSetName;
        if ($extensionSetName === null) {
            return null;
        }

        return ExtensionSet::__set_state([
            'config' => $this->getConfig(),
            'configPath' => ['extensionSets', $extensionSetName],
        ]);
    }

    protected function extensions(): array
    {
        $extensionsAll = $this->getConfig()->get('extensions') ?: [];
        $extensionsRaw = array_intersect_key(
            $extensionsAll,
            iterator_to_array($this->extensionSet->getIterator()),
        );

        $config = $this->getConfig();
        $extensions = [];
        $threadType = $this->threadType;
        foreach ($extensionsRaw as $extensionKey => $extensionRaw) {
            if (($extensionRaw['ignore'] ?? 'never') === $threadType) {
                continue;
            }

            $extensions[$extensionKey] = Extension::__set_state([
                'config' => $config,
                'configPath' => ['extensions', $extensionKey],
            ]);
        }

        return $extensions;
    }
}
