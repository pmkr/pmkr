<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Symfony\Component\Process\Process;

class ProcessFactory
{

    // region classFqn
    protected string $classFqn = Process::class;

    public function getClassFqn(): string
    {
        return $this->classFqn;
    }

    /**
     * @return $this
     */
    public function setClassFqn(string $classFqn)
    {
        $this->classFqn = $classFqn;

        return $this;
    }
    // endregion

    /**
     * @see \Symfony\Component\Process\Process::__construct()
     */
    public function createInstance(
        array $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process {
        $class = $this->getClassFqn();

        return new $class($command, $cwd, $env, $input, $timeout);
    }

    /**
     * @see \Symfony\Component\Process\Process::fromShellCommandline()
     */
    public function fromShellCommandline(
        string $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process {
        $callable = $this->getClassFqn() . '::fromShellCommandline';

        return $callable($command, $cwd, $env, $input, $timeout);
    }
}
