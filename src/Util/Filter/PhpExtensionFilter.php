<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util\Filter;

use Pmkr\Pmkr\Model\Collection;
use Sweetchuck\Utils\Filter\ArrayFilterBase;

class PhpExtensionFilter extends ArrayFilterBase
{

    // region extensionSet

    /**
     * @var null|\Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\ExtensionSetItem>
     */
    protected ?Collection $extensionSet = null;

    /**
     * @return null|\Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\ExtensionSetItem>
     */
    public function getExtensionSet(): ?Collection
    {
        return $this->extensionSet;
    }

    /**
     * @param null|\Pmkr\Pmkr\Model\Collection<\Pmkr\Pmkr\Model\ExtensionSetItem> $extensionSet
     *
     * @return $this
     */
    public function setExtensionSet(?Collection $extensionSet)
    {
        $this->extensionSet = $extensionSet;

        return $this;
    }
    // endregion

    // region ignore
    /**
     * @var string[]
     */
    protected array $ignore = [];

    /**
     * @return string[]
     */
    public function getIgnore(): array
    {
        return $this->ignore;
    }

    /**
     * @param string[] $ignore
     *
     * @return $this
     */
    public function setIgnore(array $ignore)
    {
        $this->ignore = $ignore;

        return $this;
    }
    // endregion

    // region status
    /**
     * @var array<string>
     */
    protected array $status = [];

    /**
     * @return array<string>
     */
    public function getStatus(): array
    {
        return $this->status;
    }

    /**
     * @param array<string> $status
     *
     * @return $this
     */
    public function setStatus(array $status)
    {
        $this->status = $status;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('extensionSet', $options)) {
            $this->setExtensionSet($options['extensionSet']);
        }

        if (array_key_exists('ignore', $options)) {
            $this->setIgnore($options['ignore']);
        }

        if (array_key_exists('status', $options)) {
            $this->setStatus($options['status']);
        }

        return $this;
    }

    /**
     * @param \Pmkr\Pmkr\Model\Extension $item
     *
     * @return $this
     */
    protected function checkDoIt($item, ?string $outerKey = null)
    {
        /** @var \Pmkr\Pmkr\Model\Extension $item */
        $this->result = true;

        $extensionSet = $this->getExtensionSet();
        if ($extensionSet !== null) {
            if (!$extensionSet->offsetExists($item['key'])) {
                $this->result = false;

                return $this;
            }
        }

        $allowedIgnoreValues = $this->getIgnore();
        if ($allowedIgnoreValues !== []) {
            if (!in_array($item['ignore'], $allowedIgnoreValues)) {
                $this->result = false;

                return $this;
            }
        }

        $allowedStatusValues = $this->getStatus();
        if ($allowedStatusValues !== []
            && $extensionSet
            && $extensionSet->offsetExists($item['key'])
        ) {
            $extSetItem = $extensionSet[$item['key']];
            if (!in_array($extSetItem->status, $allowedStatusValues)) {
                $this->result = false;

                return $this;
            }
        }

        return $this;
    }
}
