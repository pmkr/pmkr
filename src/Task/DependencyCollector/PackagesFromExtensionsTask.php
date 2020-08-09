<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;

class PackagesFromExtensionsTask extends BaseTask implements StateAwareInterface
{

    use StateAwareTrait;

    protected string $taskName = 'pmkr - Collect package dependencies from an extension';

    protected Utils $utils;

    protected iterable $extensions = [];

    /**
     * @return \Pmkr\Pmkr\Model\Extension[]
     */
    public function getExtensions(): iterable
    {
        return $this->extensions;
    }

    /**
     * @return $this
     */
    public function setExtensions(iterable $extensions)
    {
        $this->extensions = $extensions;

        return $this;
    }

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

    public function __construct(Utils $utils)
    {
        $this->utils = $utils;
    }

    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('state', $options)) {
            $this->setState($options['state']);
        }

        if (array_key_exists('opSys', $options)) {
            $this->setOpSys($options['opSys']);
        }

        if (array_key_exists('extensions', $options)) {
            $this->setExtensions($options['extensions']);
        }

        return $this;
    }

    protected function runHeader()
    {
        $this->printTaskInfo(
            'PMKR - collect package dependencies from extensions',
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $opSys = $this->getOpSys();
        $this->assets = [
            'packageManager.repositories' => [],
            'packageManager.packages' => [],
        ];
        foreach ($this->getExtensions() as $extension) {
            $this->assets['packageManager.packages'] += $this
                ->utils
                ->fetchPackageDependenciesFromExtension($opSys, $extension);
        }

        return $this;
    }
}
