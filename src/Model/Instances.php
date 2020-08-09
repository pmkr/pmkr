<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Instance offsetGet($offset)
 * @method \Pmkr\Pmkr\Model\Instance[]|\Traversable getIterator()
 *
 * @var \Pmkr\Pmkr\Model\Instance[]
 */
class Instances extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Instance::class,
        ],
    ];
}
