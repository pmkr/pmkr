<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Core offsetGet($offset)
 *
 * @var \Pmkr\Pmkr\Model\Core[]
 */
class Cores extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Core::class,
        ],
    ];
}
