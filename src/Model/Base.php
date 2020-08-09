<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;

/**
 * @todo Every property can cause conflict with the config schema.
 */
class Base implements ConfigAwareInterface, \ArrayAccess, \JsonSerializable
{
    use ConfigAwareTrait;

    protected array $configPath = [];

    /**
     * @return string[]
     */
    public function getConfigPath(): array
    {
        return $this->configPath;
    }

    protected array $propertyMapping = [];

    public static function __set_state(array $values)
    {
        $self = new static();
        $self->setConfig($values['config']);
        $self->configPath = $values['configPath'] ?? [];

        return $self;
    }

    public function __get($name)
    {
        $path = $this->configPath($name);
        $config = $this->getConfig();
        if (array_key_exists($name, $this->propertyMapping)) {
            $meta = $this->propertyMapping[$name];
        } elseif (array_key_exists('', $this->propertyMapping)) {
            $meta = $this->propertyMapping[''];
        } else {
            throw new \InvalidArgumentException(sprintf(
                'property not exists %s::%s',
                static::class,
                $name,
            ));
        }

        $meta += [
            'type' => 'scalar',
        ];

        if ($meta['type'] === 'scalar') {
            return $config->get($path) ?? $meta['default'] ?? null;
        }

        if ($meta['type'] === 'callback') {
            $method = $meta['callback'];

            return $this->{$method}($meta);
        }

        return call_user_func(
            [$meta['type'], '__set_state'],
            [
                'config' => $config,
                'configPath' => array_merge($this->configPath, [$name]),
            ],
        );
    }

    // region JsonSerializable
    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $values = [];
        foreach ($this->propertyMapping as $key => $meta) {
            $type = $meta['type'] ?? 'scalar';
            if ($type !== 'scalar') {
                continue;
            }

            $values[$key] = $this->$key;
        }

        return array_filter(
            $values,
            function ($value): bool {
                return $value !== null;
            },
        );
    }

    // endregion

    # region \ArrayAccess
    public function offsetExists($offset): bool
    {
        return $this->getConfig()->has($this->configPath($offset));
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        if ($value instanceof Base) {
            $value = $value->getConfig()->get($value->configPath());
        }

        $this->getConfig()->set($this->configPath($offset), $value);
    }

    public function offsetUnset($offset): void
    {
        // @todo Almost.
        $this->getConfig()->set($this->configPath($offset), null);
    }
    # endregion

    protected function configPath(?string $name = null): string
    {
        $path = $this->configPath;
        if ($name !== null) {
            $path[] = $name;
        }

        return implode('.', $path);
    }
}
