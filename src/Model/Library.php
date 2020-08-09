<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $key
 * @property-read string $name
 * @property-read string $description
 * @property-read array $downloader
 * @property-read bool[] $patches
 * @property-read array $compiler
 */
class Library extends Base
{
    protected array $propertyMapping = [
        'key' => [],
        'name' => [],
        'description' => [],
        'downloader' => [],
        'patches' => [
            'default' => [],
        ],
        'compiler' => [],
    ];
}
