<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\ExtensionSet offsetGet($offset)
 * @method \Pmkr\Pmkr\Model\ExtensionSet[]|\Traversable getIterator()
 *
 * @var \Pmkr\Pmkr\Model\Instance[]
 */
class ExtensionSets extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => ExtensionSet::class,
        ],
    ];
}
