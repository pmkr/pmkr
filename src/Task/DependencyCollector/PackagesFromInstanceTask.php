<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Utils\Filter\FilterInterface;

class PackagesFromInstanceTask extends BaseTask
{

    protected string $taskName = 'pmkr - Collect package dependencies from an instance';

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
     * @var null|\Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension>
     */
    protected ?FilterInterface $extensionFilter = null;

    /**
     * @return null|\Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension>
     */
    public function getExtensionFilter(): ?FilterInterface
    {
        return $this->extensionFilter;
    }

    /**
     * @param \Sweetchuck\Utils\Filter\FilterInterface<\Pmkr\Pmkr\Model\Extension> $extensionFilter
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
            'PMKR - collect package dependencies',
        );

        return $this;
    }

    protected function runDoIt(): static
    {
        $opSys = $this->getOpSys();
        $instance = $this->getInstance();

        $this->assets = [
            'packageManager.repositories' => [],
            'packageManager.packages' => [],
        ];

        $this->assets['packageManager.packages'] += $this
            ->utils
            ->fetchPackageDependenciesFromInstanceCore(
                $opSys,
                $instance,
            );

        $extensions = $this->getExtensions();
        if ($extensions !== null) {
            foreach ($extensions as $extension) {
                $this->assets['packageManager.packages'] += $this
                    ->utils
                    ->fetchPackageDependenciesFromExtension(
                        $opSys,
                        $extension,
                    );
            }

            return $this;
        }

        $this->assets['packageManager.packages'] += $this
            ->utils
            ->fetchPackageDependenciesFromInstanceExtensions(
                $opSys,
                $instance,
                $this->getExtensionFilter(),
            );

        return $this;
    }
}
