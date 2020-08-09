<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Utils\ArrayFilterInterface;

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

    /**
     * @return $this
     */
    public function setOpSys(?OpSys $os)
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

    /**
     * @return $this
     */
    public function setInstance(Instance $instance)
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    // region extensionFilter
    protected ?ArrayFilterInterface $extensionFilter = null;

    public function getExtensionFilter(): ?ArrayFilterInterface
    {
        return $this->extensionFilter;
    }

    /**
     * @return $this
     */
    public function setExtensionFilter(?ArrayFilterInterface $extensionFilter)
    {
        $this->extensionFilter = $extensionFilter;

        return $this;
    }
    // endregion

    // region extensions
    protected ?iterable $extensions = null;

    public function getExtensions(): ?iterable
    {
        return $this->extensions;
    }

    /**
     * If this not null then the extensionFilter won't be used.
     *
     * @return $this
     */
    public function setExtensions(?iterable $extensions)
    {
        $this->extensions = $extensions;

        return $this;
    }
    // endregion

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    public function setOptions(array $options)
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

    protected function runHeader()
    {
        $this->printTaskInfo(
            'PMKR - collect package dependencies',
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
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
