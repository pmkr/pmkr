<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\PackageManager;

abstract class HandlerBase implements HandlerInterface
{

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
