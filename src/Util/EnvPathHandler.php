<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\Application;
use Pmkr\Pmkr\Model\Instance;

class EnvPathHandler implements ConfigAwareInterface
{

    use ConfigAwareTrait;

    protected ?string $pathSeparator = null;

    public function getPathSeparator(): string
    {
        return $this->pathSeparator ?? \PATH_SEPARATOR;
    }

    public function setPathSeparator(?string $value)
    {
        $this->pathSeparator = $value;

        return $this;
    }

    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    public function explode(string $envPath): array
    {
        return array_filter(
            explode($this->getPathSeparator(), $envPath),
            'strlen',
        );
    }

    public function implode(array $paths): string
    {
        return implode($this->getPathSeparator(), array_unique($paths));
    }

    public function remove(string $envPath): string
    {
        $pattern = $this->getPattern();
        $paths = $this->explode($envPath);
        for ($i = 0; $i < count($paths); $i++) {
            if (preg_match($pattern, $paths[$i]) === 1) {
                unset($paths[$i]);
            }
        }

        return $this->implode($paths);
    }

    public function override(string $envPath, Instance $instance): string
    {
        $paths = [];
        $pattern = $this->getPattern();
        $isOverridden = false;
        foreach ($this->explode($envPath) as $path) {
            $isMatch = preg_match($pattern, $path) === 1;
            if (!$isOverridden && $isMatch) {
                $paths[] = $instance->shareDir . '/bin';
                $paths[] = $instance->shareDir . '/sbin';
                $isOverridden = true;

                continue;
            }

            if (!$isMatch) {
                $paths[] = $path;
            }
        }

        if (!$isOverridden) {
            array_unshift(
                $paths,
                $instance->shareDir . '/bin',
                $instance->shareDir . '/sbin',
            );
        }

        return $this->implode($paths);
    }

    public function getCurrentInstanceName(string $envPath): ?string
    {
        $pattern = $this->getPattern();
        foreach ($this->explode($envPath) as $path) {
            $matches = [];
            if (preg_match($pattern, $path, $matches)) {
                return $matches['instanceName'];
            }
        }

        return null;
    }

    protected function getPattern(): string
    {
        $shareDir = $this->getConfig()->get('dir.share');
        $prefix = Application::INSTANCE_DIR_PREFIX;

        return '@^' . preg_quote("$shareDir/$prefix-", '@') . '(?P<instanceName>[^/]+?)/(s?)bin$@';
    }
}
