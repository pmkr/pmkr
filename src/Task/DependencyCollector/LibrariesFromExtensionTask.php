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

    protected string $taskName = 'pmkr - Collect library dependencies from extension {extensionName}';

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

    /**
     * @return $this
     */
    public function setOpSys(?OpSys $os)
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
     *
     * @return $this
     */
    public function setExtension(?Extension $extension)
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
     *
     * @return $this
     */
    public function setOptions(array $options)
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

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
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
