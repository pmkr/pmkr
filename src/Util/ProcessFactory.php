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

    public function setClassFqn(string $classFqn): static
    {
        $this->classFqn = $classFqn;

        return $this;
    }
    // endregion

    /**
     * @param array<string> $command
     * @param array<string, string> $env
     * @param null|resource $input
     *
     * @see \Symfony\Component\Process\Process::__construct()
     */
    public function createInstance(
        array $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60,
    ): Process {
        /** @var \Symfony\Component\Process\Process $class */
        $class = $this->getClassFqn();

        return new $class($command, $cwd, $env, $input, $timeout);
    }

    /**
     * @param array<string, string> $env
     * @param null|resource $input
     *
     * @see \Symfony\Component\Process\Process::fromShellCommandline()
     */
    public function fromShellCommandline(
        string $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60,
    ): Process {
        /**
         * @var callable(
         *     string $command,
         *     ?string $cwd,
         *     ?array<string, string> $env,
         *     ?resource $input,
         *     ?float $timeout
         * ): \Symfony\Component\Process\Process $callable
         */
        $callable = $this->getClassFqn() . '::fromShellCommandline';

        return $callable($command, $cwd, $env, $input, $timeout);
    }
}
