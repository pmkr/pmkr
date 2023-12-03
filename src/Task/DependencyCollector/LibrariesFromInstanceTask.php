<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Model\Collection;
use Pmkr\Pmkr\Model\Library;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Utils\Filter\FilterInterface;

class LibrariesFromInstanceTask extends BaseTask
{

    protected string $taskName = 'pmkr - Collect library dependencies from an instance';

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $instance = $this->getInstance();
        if ($instance) {
            $context['instanceKey'] = $instance->key;
        }

        return $context;
    }

    protected Utils $utils;

    //region opSys
    protected ?OpSys $opSys = null;

    public function getOpSys(): ?OpSys
    {
        return $this->opSys;
    }

    public function setOpSys(?OpSys $os): static
    {
        $this->opSys = $os;

        return $this;
    }
    //endregion

    // region instance
    protected ?Instance $instance = null;

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    public function setInstance(Instance $instance): static
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    // region extensionFilter
    /**
     * @phpstan-var null|\Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension>
     */
    protected ?FilterInterface $extensionFilter = null;

    /**
     * @phpstan-return null|\Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension>
     */
    public function getExtensionFilter(): ?FilterInterface
    {
        return $this->extensionFilter;
    }

    /**
     * @phpstan-param null|\Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension> $extensionFilter
     */
    public function setExtensionFilter(?FilterInterface $extensionFilter): static
    {
        $this->extensionFilter = $extensionFilter;

        return $this;
    }
    // endregion

    // region extensions
    /**
     * @var ?iterable<string, \Pmkr\Pmkr\Model\Extension>
     */
    protected ?iterable $extensions = null;

    /**
     * @return ?iterable<string, \Pmkr\Pmkr\Model\Extension>
     */
    public function getExtensions(): ?iterable
    {
        return $this->extensions;
    }

    /**
     * If this not null then the extensionFilter won't be used.
     *
     * @param ?iterable<string, \Pmkr\Pmkr\Model\Extension> $extensions
     */
    public function setExtensions(?iterable $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }
    // endregion

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);

        if (array_key_exists('opSys', $options)) {
            $this->setOpSys($options['opSys']);
        }

        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        if (array_key_exists('extensionFilter', $options)) {
            $this->setExtensionFilter($options['extensionFilter']);
        }

        if (array_key_exists('extensions', $options)) {
            $this->setExtensions($options['extensions']);
        }

        return $this;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'Instance key: {instance.key}',
            [
                'instance.key' => $this->getInstance()->key,
            ],
        );

        return $this;
    }

    protected function runDoIt(): static
    {
        $instance = $this->getInstance();
        $config = $instance->getConfig();
        $configPathRoot = array_slice($instance->getConfigPath(), 0, -2);
        $librariesAll = Collection::__set_state([
            'config' => $config,
            'configPath' => array_merge($configPathRoot, ['libraries']),
            'propertyMapping' => [
                '' => [
                    'type' => Library::class,
                ],
            ],
        ]);
        $librariesAll = iterator_to_array($librariesAll);

        $this->assets = [
            'libraries' => [],
        ];

        $opSys = $this->getOpSys();
        $libraryKeys = $this->utils->fetchLibraryKeys(
            $opSys,
            $instance->core->dependencies['libraries'] ?? [],
        );

        $extensions = $this->getExtensions();
        if ($extensions === null) {
            $filter = $this->getExtensionFilter();
            $extensions = $filter ?
                array_filter($instance->extensions, $filter)
                : $instance->extensions;
        }

        foreach ($extensions as $extension) {
            $libraryKeys += $this->utils->fetchLibraryKeys(
                $opSys,
                $extension->dependencies['libraries'] ?? [],
            );
        }

        $this->assets['libraries'] = array_intersect_key($librariesAll, $libraryKeys);

        return $this;
    }
}
