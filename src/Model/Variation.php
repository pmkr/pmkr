<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Model;

/**
 * @property-read null|string $key
 * @property-read string $instanceKey
 * @property-read \Pmkr\Pmkr\Model\Instance $instance
 * @property-read null|string $phpRc
 * @property-read null|string[] $phpIniScanDir
 */
class Variation extends Base
{
    protected array $propertyMapping = [
        'key' => [],
        'instanceKey' => [],
        'instance' => [
            'type' => 'callback',
            'callback' => 'instance',
        ],
        'phpRc' => [],
        'phpIniScanDir' => [],
    ];

    protected function instance(): ?Instance
    {
        $config = $this->getConfig();
        $instanceKey = $this->instanceKey;

        if ($instanceKey && $config->has("aliases.$instanceKey")) {
            $instanceKey = $config->get("aliases.$instanceKey");
        }

        if (!$instanceKey || !$config->has("instances.$instanceKey")) {
            return null;
        }

        return Instance::__set_state([
            'config' => $config,
            'configPath' => ['instances', $instanceKey],
        ]);
    }
}
