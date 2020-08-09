<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read string $name
 * @property-read string $label
 * @property-read string $version
 * @property-read array $update
 */
class App extends Base
{
    protected array $propertyMapping = [
        'name' => [],
        'label' => [],
        'version' => [],
        'update' => [],
    ];
}
