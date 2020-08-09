<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OpSys;

use Sweetchuck\Utils\VersionNumber;

class OpSys
{

    protected array $state = [];

    /**
     * @return static
     */
    public static function __set_state(array $state)
    {
        $self = new static();
        $self->state = $state;

        return $self;
    }

    public function __construct(?OpSysInfoCollector $infoCollector = null)
    {
        if ($infoCollector) {
            $this->state = $infoCollector->get();
        }
    }

    public function id(): ?string
    {
        return $this->state['ID'] ?? null;
    }

    public function idLike(): ?array
    {
        return $this->state['ID_LIKE'] ?? null;
    }

    public function versionId(): ?string
    {
        return $this->state['VERSION_ID'] ?? null;
    }

    /**
     * @todo Can be more than one. OSX Homebrew, MacPorts, Fink etc...
     */
    public function packageManager(): ?string
    {
        $idLike = (array) $this->idLike();
        $idLike = $idLike[0] ?? $this->id();
        switch ($idLike) {
            case 'opensuse':
            case 'suse':
                return 'zypper';

            case 'ubuntu':
            case 'debian':
                return 'apt';

            case 'fedora':
                return 'dnf';

            case 'arch':
                return 'pacman';
        }

        return null;
    }

    /**
     * @param string[] $identifiers
     *   Example: ["opensuse-tumbleweed", "ubuntu-21-10"]
     */
    public function pickOpSysIdentifier(array $identifiers): ?string
    {
        $id = str_replace('.', '-', $this->id());
        $versionId = str_replace('.', '-', (string) $this->versionId());
        $idWithVersionId = $id . ($versionId ? "-$versionId" : '');
        if (in_array($idWithVersionId, $identifiers)) {
            return $idWithVersionId;
        }

        usort($identifiers, 'version_compare');
        foreach (array_reverse($identifiers) as $identifier) {
            if (strpos($identifier, $id) === 0
                && version_compare($identifier, $idWithVersionId, '<=')
            ) {
                return $identifier;
            }
        }

        return isset($identifiers[$id]) ? $id : null;
    }

    public function versionNumber(): VersionNumber
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function isLinux(): bool
    {
        return $this->state['os_family'] ?? '' === 'Linux';
    }

    public function isUnix(): bool
    {
        // @todo Improve.
        return $this->state['os_family'] ?? '' === 'Linux';
    }

    public function isBsd(): bool
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function isSolaris(): bool
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function isWindows(): bool
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function isOsx(): bool
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function family(): ?string
    {
        return $this->state['os_family'] ?? null;
    }

    public function isIdLikeOneOf(array $expected): bool
    {
        return count(array_intersect($this->idLike(), $expected)) > 0;
    }
}
