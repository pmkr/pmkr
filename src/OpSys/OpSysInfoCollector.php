<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OpSys;

class OpSysInfoCollector
{

    protected array $state = [];

    /**
     * @see \PHP_OS
     * @see \PHP_OS_FAMILY
     */
    public function get(): array
    {
        $this->init();

        return $this->state;
    }

    protected function init()
    {
        if ($this->state) {
            return $this;
        }

        $this->state = [
            'php_os' => \PHP_OS,
            'php_os_family' => \PHP_OS_FAMILY,
        ];

        if ($this->state['php_os'] === 'Linux') {
            $this->linux();
        }

        return $this;
    }

    protected function linux()
    {
        $this->parseEtcOsRelease();

        return $this;
    }

    protected function parseEtcOsRelease()
    {
        $fileName = '/etc/os-release';
        if (!file_exists($fileName)) {
            return $this;
        }

        $this->state += (array) parse_ini_file($fileName);
        if (array_key_exists('ID_LIKE', $this->state) && $this->state['ID_LIKE'] !== '') {
            $this->state['ID_LIKE'] = explode(' ', $this->state['ID_LIKE']);
        }

        return $this;
    }
}
