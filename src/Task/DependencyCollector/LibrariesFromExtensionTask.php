<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;

class LibrariesFromExtensionTask extends BaseTask
{

    protected string $taskName = 'pmkr - Collect library dependencies from an extension';

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();

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

    // region extension
    protected ?Extension $extension = null;

    /**
     * @return null|\Pmkr\Pmkr\Model\Extension
     */
    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    /**
     * If this not null then the extensionFilter won't be used.
     */
    public function setExtension(?Extension $extension): static
    {
        $this->extension = $extension;

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

        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        return $this;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'PMKR - Extension key: {extension.key}; Extension name: {extension.name}',
            [
                'extension.key' => $this->getExtension()->key,
                'extension.name' => $this->getExtension()->name,
            ],
        );

        return $this;
    }

    protected function runDoIt(): static
    {
        $pmkr = PmkrConfig::__set_state([
            'config' => $this->getConfig(),
            'configPath' => [],
        ]);
        $opSys = $this->getOpSys();
        $extension = $this->getExtension();
        $librariesAll = iterator_to_array($pmkr->libraries->getIterator());

        $libraryKeys = [];
        if ($extension) {
            $libraryKeys += $this->utils->fetchLibraryKeys(
                $opSys,
                $extension->dependencies['libraries'] ?? [],
            );
        }

        $this->assets['libraries'] = array_intersect_key($librariesAll, $libraryKeys);

        return $this;
    }
}
