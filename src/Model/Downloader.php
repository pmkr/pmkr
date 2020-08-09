<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $type
 * @property-read array $options
 */
class Downloader extends Base
{
    protected array $propertyMapping = [
        'type' => [
            'default' => 'pecl',
        ],
        'options' => [
            'default' => [],
        ],
    ];
}
