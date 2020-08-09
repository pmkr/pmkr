<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read null|string $key
 * @property-read string $status
 * @property-read bool $isEnabled
 * @property-read array $etc
 * @property-read string $extensionName
 * @property-read \Pmkr\Pmkr\Model\Extension $extension
 */
class ExtensionSetItem extends Base
{
    protected array $propertyMapping = [
        'key' => [],
        'status' => [
            'default' => 'optional',
        ],
        'isEnabled' => [
            'default' => false,
        ],
        'etc' => [],
        'extensionName' => [
            'type' => 'callback',
            'callback' => 'extensionName',
        ],
        'extension' => [
            'type' => 'callback',
            'callback' => 'extension',
        ],
    ];

    protected function extensionName(): string
    {
        $parts = explode('-', $this->key);

        return (string) reset($parts);
    }

    protected function extension(): Extension
    {
        return Extension::__set_state([
            'config' => $this->getConfig(),
            'configPath' => ['extensions', $this->key],
        ]);
    }
}
