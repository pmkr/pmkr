<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Instance;

use Sweetchuck\Utils\Comparer\ArrayValueComparer;

/**
 * @todo Move this into sweetchuck/utils.
 */
class InstanceComparer extends ArrayValueComparer
{
    /**
     * @param \Pmkr\Pmkr\Model\Instance $a
     * @param \Pmkr\Pmkr\Model\Instance $b
     *
     * @return $this
     */
    public function setResult($a, $b)
    {
        return parent::setResult($a->jsonSerialize(), $b->jsonSerialize());
    }
}
