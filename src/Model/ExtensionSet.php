<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\ExtensionSetItem offsetGet($offset)
 * @method \Pmkr\Pmkr\Model\ExtensionSetItem[]|\Traversable getIterator()
 *
 * @var \Pmkr\Pmkr\Model\ExtensionSetItem[]
 */
class ExtensionSet extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => ExtensionSetItem::class,
        ],
    ];
}
