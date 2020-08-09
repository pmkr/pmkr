<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Helper\Dummy;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    protected OutputInterface $stdError;

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface|\Symfony\Component\Console\Output\BufferedOutput
     */
    public function getErrorOutput()
    {
        return $this->stdError;
    }

    public function setErrorOutput(OutputInterface $error)
    {
        $this->stdError = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        $sections = [];
        $stream = fopen('/dev/null', 'w');

        return new ConsoleSectionOutput(
            $stream,
            $sections,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter(),
        );
    }
}
