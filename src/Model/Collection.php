<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @template TValue
 * @implements \IteratorAggregate<string, TValue>
 * @implements \ArrayAccess<string, TValue>
 */
class Collection extends Base implements \IteratorAggregate, \ArrayAccess
{

    /**
     * @return \Traversable<string, TValue>
     */
    public function getIterator(): \Traversable
    {
        $data = (array) $this->getConfig()->get($this->configPath());
        foreach (array_keys($data) as $key) {
            $data[$key] = $this[$key];
        }

        return new \ArrayIterator($data);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $values = [];
        /** @var int|string $key */
        /** @var \Pmkr\Pmkr\Model\Base $value */
        foreach ($this as $key => $value) {
            $values[$key] = $value->jsonSerialize();
        }

        return $values;
    }
}
