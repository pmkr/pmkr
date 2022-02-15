<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Helper\Dummy;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    protected BufferedOutput $stdError;

    /**
     * @return \Symfony\Component\Console\Output\BufferedOutput
     */
    public function getErrorOutput()
    {
        return $this->stdError;
    }

    /**
     * @param \Symfony\Component\Console\Output\BufferedOutput $error
     *
     * @return void
     */
    public function setErrorOutput(OutputInterface $error)
    {
        $this->stdError = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        $sections = [];
        $stream = fopen('/dev/null', 'w');
        if ($stream === false) {
            throw new \RuntimeException('file /dev/null could not be opened for writing', 1);
        }

        return new ConsoleSectionOutput(
            $stream,
            $sections,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter(),
        );
    }
}
