<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @method \Pmkr\Pmkr\Model\Checksum offsetGet($offset)
 *
 * @var \Pmkr\Pmkr\Model\Checksum[]
 */
class Checksums extends IterableBase
{
    protected array $propertyMapping = [
        '' => [
            'type' => Checksum::class,
        ],
    ];
}
