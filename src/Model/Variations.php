<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Variation offsetGet($offset)
 * @method \Pmkr\Pmkr\Model\Variation[]|\Traversable getIterator()
 *
 * @var \Pmkr\Pmkr\Model\Variation[]
 */
class Variations extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Variation::class,
        ],
    ];
}
