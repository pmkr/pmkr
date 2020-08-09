<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Extension offsetGet($offset)
 *
 * @var \Pmkr\Pmkr\Model\Extension[]
 */
class Extensions extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Extension::class,
        ],
    ];
}
