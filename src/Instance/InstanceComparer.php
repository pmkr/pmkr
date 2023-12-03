<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Instance;

use Sweetchuck\Utils\Comparer\ArrayValueComparer;

/**
 * @extends \Sweetchuck\Utils\Comparer\ArrayValueComparer<\Pmkr\Pmkr\Model\Instance>
 */
class InstanceComparer extends ArrayValueComparer
{

    /**
     * {@inheritdoc}
     */
    public function setResult($a, $b): static
    {
        return parent::setResult($a->jsonSerialize(), $b->jsonSerialize());
    }
}
