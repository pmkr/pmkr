<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util\Filter;

use Composer\Semver\VersionParser;
use Pmkr\Pmkr\OpSys\OpSys;
use Sweetchuck\Utils\Filter\FilterBase;

/**
 * @extends \Sweetchuck\Utils\Filter\FilterBase<\Pmkr\Pmkr\Model\Patch>
 */
class PatchFilter extends FilterBase
{

    protected VersionParser $versionParser;

    public function __construct(VersionParser $versionParser)
    {
        $this->versionParser = $versionParser;
    }

    // region opSys
    protected ?OpSys $opSys = null;

    public function getOpSys(): ?OpSys
    {
        return $this->opSys;
    }

    /**
     * @param \Pmkr\Pmkr\OpSys\OpSys $opSys
     *   Actual OpSys definition.
     */
    public function setOpSys(?OpSys $opSys): static
    {
        $this->opSys = $opSys;

        return $this;
    }
    // endregion

    // region version
    protected ?string $version = null;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param null|string $version
     *   Version of the code base (core, extension or library).
     */
    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);
        if (array_key_exists('opSys', $options)) {
            $this->setOpSys($options['opSys']);
        }

        if (array_key_exists('version', $options)) {
            $this->setVersion($options['version']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function setResult(mixed $item, null|int|string $outerKey = null): static
    {
        $version = $this->getVersion();
        $versionConstraint = $item->when['versionConstraint'] ?? null;
        $this->result = true;
        if ($version !== null && $versionConstraint !== null) {
            $this->result = $this
                ->versionParser
                ->parseConstraints($version)
                ->matches($this->versionParser->parseConstraints($versionConstraint));
        }

        $opSys = $this->getOpSys();
        if ($this->result === true && $opSys) {
            $opSysIdCandidates = array_keys($item->when['opSys'] ?? []);
            $opSysId = $opSys->pickOpSysIdentifier($opSysIdCandidates) ?? 'default';
            $this->result = $item->when['opSys'][$opSysId] ?? true;
        }

        return $this;
    }
}
