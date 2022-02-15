<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

interface HandlerInterface
{

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array;

    /**
     * @param array<string, mixed> $config
     *
     * @return $this
     */
    public function setConfig(array $config);

    /**
     * @param array<string> $packageNames
     *
     * @return array{
     *     messages?: array<string>,
     *     missing?: array<string>,
     *     installed: array<string, array{
     *         name?: string,
     *         type?: string,
     *         architecture?: ?string,
     *         status?: string|array<string>,
     *         version?: ?string,
     *     }>,
     *     not-installed: array<string, array{
     *         name?: string,
     *         type?: string,
     *         architecture?: ?string,
     *         status?: string|array<string>,
     *         version?: ?string,
     *     }>,
     * }
     */
    public function missing(array $packageNames): array;

    /**
     * @param array<string> $packageNames
     */
    public function installCommand(array $packageNames): string;

    /**
     * @param array<string> $packageNames
     *
     * @return $this
     */
    public function install(array $packageNames);

    public function refreshCommand(): string;
}
