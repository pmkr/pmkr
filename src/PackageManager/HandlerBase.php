<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

abstract class HandlerBase implements HandlerInterface
{

    protected array $config = [];

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }
}
