<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util\Filter;

use Composer\Semver\VersionParser;
use Sweetchuck\Utils\Filter\ArrayFilterBase;
use Symfony\Component\Filesystem\Filesystem;

class InstanceFilter extends ArrayFilterBase
{
    protected Filesystem $fs;

    protected VersionParser $versionParser;

    public function __construct(Filesystem $fs, VersionParser $versionParser)
    {
        $this->fs = $fs;
        $this->versionParser = $versionParser;
    }

    protected ?bool $hasSrcDir = null;

    public function getHasSrcDir(): ?bool
    {
        return $this->hasSrcDir;
    }

    /**
     * @return $this
     */
    public function setHasSrcDir(?bool $hasSrcDir)
    {
        $this->hasSrcDir = $hasSrcDir;

        return $this;
    }

    protected ?bool $hasShareDir = null;

    public function getHasShareDir(): ?bool
    {
        return $this->hasShareDir;
    }

    /**
     * @return $this
     */
    public function setHasShareDir(?bool $hasShareDir)
    {
        $this->hasShareDir = $hasShareDir;

        return $this;
    }

    // region primaryCoreVersionConstraints
    protected ?string $primaryCoreVersionConstraints = null;

    public function getPrimaryCoreVersionConstraints(): ?string
    {
        return $this->primaryCoreVersionConstraints;
    }

    /**
     * Core version has to match to this constraint even if the
     * ::getCoreVersionConstraints() defines a wider range of core versions.
     *
     * Why there are two coreVersionConstraints?
     * Very likely there would be a more cleaner way to filter based on the
     * core version, but this one was easy to implement.
     * These filters are support the "instance:pick:project" command, to pick
     * an instance based on the requirements defined in the composer.{json,lock}.
     * Dependencies might define this range:
     * 7.1, 7.2, 7.3, 7.4, 8.0, 8.1
     * If all of them is available then the 7.1 would be picked.
     * But the root project uses a constraint like this: >=7.4 <8.1
     * That means the 7.1, 7.2, 7.3, 8.1 can not be picked.
     *
     * @return $this
     */
    public function setPrimaryCoreVersionConstraints(?string $constraints)
    {
        $this->primaryCoreVersionConstraints = $constraints;

        return $this;
    }
    // endregion

    // region coreVersionConstraints
    protected ?string $coreVersionConstraints = null;

    public function getCoreVersionConstraints(): ?string
    {
        return $this->coreVersionConstraints;
    }

    /**
     * @return $this
     */
    public function setCoreVersionConstraints(?string $constraints)
    {
        $this->coreVersionConstraints = $constraints;

        return $this;
    }
    // endregion

    // region isZts
    protected ?bool $isZts = null;

    public function getIsZts(): ?bool
    {
        return $this->isZts;
    }

    /**
     * @return $this
     */
    public function setIsZts(?bool $isZts)
    {
        $this->isZts = $isZts;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('hasSrcDir', $options)) {
            $this->setHasSrcDir($options['hasSrcDir']);
        }

        if (array_key_exists('hasShareDir', $options)) {
            $this->setHasShareDir($options['hasShareDir']);
        }

        if (array_key_exists('primaryCoreVersionConstraints', $options)) {
            $this->setPrimaryCoreVersionConstraints($options['primaryCoreVersionConstraints']);
        }

        if (array_key_exists('coreVersionConstraints', $options)) {
            $this->setCoreVersionConstraints($options['coreVersionConstraints']);
        }

        if (array_key_exists('isZts', $options)) {
            $this->setIsZts($options['isZts']);
        }

        return $this;
    }

    /**
     * @param \Pmkr\Pmkr\Model\Instance $item
     *
     * @return $this
     */
    protected function checkDoIt($item, ?string $outerKey = null)
    {
        $this->result = true;

        $hasSrcDir = $this->getHasSrcDir();
        if ($hasSrcDir !== null) {
            $this->result = $hasSrcDir === $this->fs->exists($item->srcDir);
        }

        $hasShareDir = $this->getHasShareDir();
        if ($this->result && $hasShareDir !== null) {
            $this->result = $hasShareDir === $this->fs->exists($item->shareDir);
        }

        $coreVersionConstraints = $this->getCoreVersionConstraints();
        if ($this->result && $coreVersionConstraints !== null) {
            $this->result = $this
                ->versionParser
                ->parseConstraints($item->coreVersion)
                ->matches($this->versionParser->parseConstraints($coreVersionConstraints));
        }

        $primaryCoreVersionConstraints = $this->getPrimaryCoreVersionConstraints();
        if ($this->result && $primaryCoreVersionConstraints) {
            $this->result = $this
                ->versionParser
                ->parseConstraints($item->coreVersion)
                ->matches($this->versionParser->parseConstraints($primaryCoreVersionConstraints));
        }

        $isZts = $this->getIsZts();
        if ($this->result && $isZts !== null) {
            $this->result = $isZts === $item->isZts;
        }

        return $this;
    }
}
