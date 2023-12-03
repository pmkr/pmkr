<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use Pmkr\Pmkr\Model\Library;
use Symfony\Component\Filesystem\Filesystem;

trait OptionsTrait
{
    protected Filesystem $filesystem;

    protected string $dstDir = '';

    protected string $srcDir = '';

    // region library
    protected ?Library $library = null;

    public function getLibrary(): ?Library
    {
        return $this->library;
    }

    public function setLibrary(?Library $library): static
    {
        $this->library = $library;

        return $this;
    }
    // endregion

    // region skipIfExists
    protected bool $skipIfExists = true;

    public function getSkipIfExists(): bool
    {
        return $this->skipIfExists;
    }

    public function setSkipIfExists(bool $skipIfExists): static
    {
        $this->skipIfExists = $skipIfExists;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    protected function setOptionsCommon(array $options): static
    {
        if (array_key_exists('library', $options)) {
            $this->setLibrary($options['library']);
        }

        if (array_key_exists('skipIfExists', $options)) {
            $this->setSkipIfExists($options['skipIfExists']);
        }

        return $this;
    }

    protected function runInit(): static
    {
        parent::runInit();
        $library = $this->getLibrary();
        $config = $this->getConfig();
        $this->srcDir = $config->get('dir.src') . "/$library->name";
        $this->dstDir = $config->get('dir.share') . "/$library->name";

        return $this;
    }

    protected function isSkipped(): bool
    {
        return $this->getSkipIfExists()
            && $this->filesystem->exists($this->dstDir);
    }
}
