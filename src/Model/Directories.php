<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $slash
 * @property-read string $bin
 * @property-read string $sbin
 * @property-read string $share
 * @property-read string $src
 * @property-read string $usr
 * @property-read string $log
 * @property-read string $run
 * @property-read string $cache
 * @property-read string $tmp
 * @property-read string $patch
 * @property-read string $templates
 */
class Directories extends Base
{
    protected array $propertyMapping = [
        'slash' => [],
        'bin' => [],
        'sbin' => [],
        'share' => [],
        'src' => [],
        'usr' => [],
        'log' => [],
        'run' => [],
        'cache' => [],
        'tmp' => [],
        'patch' => [],
        'templates' => [],
    ];
}
