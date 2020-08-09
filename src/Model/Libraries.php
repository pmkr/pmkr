<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Library offsetGet($offset)
 * @method \Pmkr\Pmkr\Model\Library[]|\Traversable getIterator()
 *
 * @var \Pmkr\Pmkr\Model\Library[]
 */
class Libraries extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Library::class,
        ],
    ];
}
