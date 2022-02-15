<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\DependencyCollector;

use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\OpSys\OpSys;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;

class LibrariesFromExtensionsTask extends BaseTask
{

    protected string $taskName = 'pmkr - Collect library dependencies from extensions';

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

    // region extensions
    /**
     * @var ?array<string, \Pmkr\Pmkr\Model\Extension>
     */
    protected ?iterable $extensions = null;

    /**
     * @return ?array<string, \Pmkr\Pmkr\Model\Extension>
     */
    public function getExtensions(): ?iterable
    {
        return $this->extensions;
    }

    /**
     * If this not null then the extensionFilter won't be used.
     *
     * @param ?array<string, \Pmkr\Pmkr\Model\Extension> $extensions
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

        if (array_key_exists('extensions', $options)) {
            $this->setExtensions($options['extensions']);
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
        $extensions = $this->getExtensions() ?: [];
        $libraryKeys = [];
        foreach ($extensions as $extension) {
            $libraryKeys += $this->utils->fetchLibraryKeys(
                $opSys,
                $extension->dependencies['libraries'] ?? [],
            );
        }

        $librariesAll = iterator_to_array($pmkr->libraries->getIterator());
        $this->assets['libraries'] = array_intersect_key($librariesAll, $libraryKeys);

        return $this;
    }
}
