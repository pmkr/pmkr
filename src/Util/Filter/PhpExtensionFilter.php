<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util\Filter;

use Pmkr\Pmkr\Model\Collection;
use Sweetchuck\Utils\Filter\FilterBase;

/**
 * @extends \Sweetchuck\Utils\Filter\FilterBase<\Pmkr\Pmkr\Model\Extension>
 */
class PhpExtensionFilter extends FilterBase
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
     */
    public function setExtensionSet(?Collection $extensionSet): static
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
     */
    public function setIgnore(array $ignore): static
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
     */
    public function setStatus(array $status): static
    {
        $this->status = $status;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
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
     * {@inheritdoc}
     */
    protected function setResult(mixed $item, null|int|string $outerKey = null): static
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
