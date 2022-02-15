<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read null|string $key
 * @property-read int|float $weight
 * @property-read string $name
 * @property-read null|string $version
 * @property-read \Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\Checksum> $checksums
 * @property-read string $ignore
 * @property-read \Pmkr\Pmkr\Model\Downloader $downloader
 * @property-read \Pmkr\Pmkr\Model\Compiler $compiler
 * @property-read array{
 *     packages?: array<string, array<string, bool>>,
 *     libraries?: array<string, array<string, bool>>,
 * } $dependencies
 * @property-read array<string, bool> $patches
 * @property-read array<string, array<string, false|string>> $configureEnvVar
 * @property-read array<string, array<string, null|false|string>> $configure
 * @property-read array $etc
 */
class Extension extends Base
{
    protected array $propertyMapping = [
        'key' => [],
        'weight' => [
            'default' => 50,
        ],
        'name' => [],
        'version' => [],
        'checksums' => [
            'type' => Collection::class,
            'state' => [
                'propertyMapping' => [
                    '' => [
                        'type' => Checksum::class,
                    ],
                ],
            ],
        ],
        'ignore' => [
            'default' => 'never',
        ],
        'downloader' => [
            'type' => Downloader::class,
            'default' => [
                'type' => 'pecl',
                'options' => [],
            ],
        ],
        'compiler' => [
            'type' => Compiler::class,
            'default' => [
                'type' => 'pecl',
                'options' => [],
            ],
        ],
        // @todo Model definition.
        'dependencies' => [],
        'patches' => [
            'default' => [],
        ],
        'configureEnvVar' => [
            'default' => [],
        ],
        'configure' => [
            'default' => [],
        ],
        'etc' => [
            'default' => [],
        ],
    ];
}
