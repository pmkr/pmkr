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

    /**
     * @var iterable<string, \Pmkr\Pmkr\Model\Extension>
     */
    protected iterable $extensions = [];

    /**
     * @return iterable<\Pmkr\Pmkr\Model\Extension>
     */
    public function getExtensions(): iterable
    {
        return $this->extensions;
    }

    /**
     * @param iterable<string, \Pmkr\Pmkr\Model\Extension> $extensions
     */
    public function setExtensions(iterable $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

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

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'PMKR - collect package dependencies from extensions',
        );

        return $this;
    }

    protected function runDoIt(): static
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
