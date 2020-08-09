<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

class Factory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function createInstance(string $handler, array $config): ?HandlerInterface
    {
        $instance = null;
        switch ($handler) {
            case 'apt':
            case 'dnf':
            case 'pacman':
            case 'zypper':
                $instance = $this->getContainer()->get("pmkr.package_manager.$handler");
                break;
        }

        if ($instance) {
            $instance->setConfig($config);
        }

        return $instance;
    }
}
