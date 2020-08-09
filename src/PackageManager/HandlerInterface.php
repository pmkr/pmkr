<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

interface HandlerInterface
{

    public function getConfig(): array;

    /**
     * @return $this
     */
    public function setConfig(array $config);

    public function missing(array $packageNames): array;

    public function installCommand(array $packageNames): string;

    /**
     * @return $this
     */
    public function install(array $packageNames);

    public function refreshCommand(): string;
}
