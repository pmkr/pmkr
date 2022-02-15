<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\Application;
use Symfony\Component\Finder\Finder;

class InstanceCollector
{

    /**
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function collect(string $parentDir): array
    {
        $prefix = Application::INSTANCE_DIR_PREFIX;
        $dirs = (new Finder())
            ->in($parentDir)
            ->depth(0)
            ->directories()
            ->name("$prefix-*");

        $result = [];
        foreach ($dirs as $dir) {
            $key = $this->parseInstanceKeyFromBasename($dir->getBasename());
            $result[$key] = $dir;
        }

        return $result;
    }

    /**
     * @return array<string, array{
     *     src?: string,
     *     share?: string,
     * }>
     */
    public function collectOrphans(ConfigInterface $config): array
    {
        $orphans = [];
        $validInstances = $config->get('instances');

        $existingInstances = $this->collect($config->get('dir.src'));
        foreach (array_diff_key($existingInstances, $validInstances) as $key => $dir) {
            $orphans[$key]['src'] = $dir->getPathname();
        }

        $existingInstances = $this->collect($config->get('dir.share'));
        foreach (array_diff_key($existingInstances, $validInstances) as $key => $dir) {
            $orphans[$key]['share'] = $dir->getPathname();
        }

        return $orphans;
    }

    /**
     * @param array<string, array<string, string>> $orphans
     *
     * @return array<string>
     */
    public function flattenOrphanDirs(array $orphans): array
    {
        $flat = [];
        foreach ($orphans as $dirs) {
            foreach ($dirs as $dir) {
                $flat[] = $dir;
            }
        }

        return $flat;
    }

    public function parseInstanceKeyFromBasename(string $basename): string
    {
        return substr($basename, strlen(Application::INSTANCE_DIR_PREFIX) + 1);
    }
}
