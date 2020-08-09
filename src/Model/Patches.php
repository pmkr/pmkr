<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Patch offsetGet($offset)
 *
 * @var \Pmkr\Pmkr\Model\Patch[]
 */
class Patches extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Patch::class,
        ],
    ];
}
