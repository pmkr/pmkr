<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputConverter;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Filesystem\Filesystem;

class InstanceConverter
{

    protected Utils $utils;

    protected Filesystem $fs;

    public function __construct(Utils $utils, Filesystem $fs)
    {
        $this->utils = $utils;
        $this->fs = $fs;
    }

    /**
     * @param iterable|\Pmkr\Pmkr\Model\Instance[] $instances
     * @param bool $isHuman
     *
     * @return array<
     *     string,
     *     array{
     *         key: string,
     *         coreVersion: string,
     *         isZts: bool|string,
     *         installed: bool|string,
     *         coreNameSuffix: string,
     *         coreName: string,
     *         extensionSetNameSuffix: string,
     *         extensionSetName: string,
     *     }
     * >
     */
    public function toFlatRows(iterable $instances, bool $isHuman): array
    {
        $rows = [];
        foreach ($instances as $instance) {
            $rows[$instance->key] = $this->toFlatRow($instance, $isHuman);
        }

        return $rows;
    }

    /**
     * @return array{
     *     key: string,
     *     coreVersion: string,
     *     isZts: bool|string,
     *     installed: bool|string,
     *     coreNameSuffix: string,
     *     coreName: string,
     *     extensionSetNameSuffix: string,
     *     extensionSetName: string,
     * }
     */
    public function toFlatRow(Instance $instance, bool $isHuman): array
    {
        $row = [
            'key' => $instance->key,
            'coreVersion' => $instance->coreVersion,
            'isZts' => $instance->isZts,
            'installed' => $this->fs->exists($instance->shareDir),
            'coreNameSuffix' => $instance->coreNameSuffix,
            'coreName' => (string) $instance->coreName,
            'extensionSetNameSuffix' => (string) $instance->extensionSetNameSuffix,
            'extensionSetName' => (string) $instance->extensionSetName,
        ];

        if ($isHuman) {
            $isInstalled = $row['installed'];
            $row['key'] = sprintf(
                '<fg=%s>%s</>',
                $isInstalled ? 'green' : 'red',
                $row['key'],
            );

            $row['isZts'] = $this->utils->boolToAnsi($row['isZts']);
            $row['installed'] = $this->utils->boolToAnsi($row['installed']);
        }

        return $row;
    }
}
