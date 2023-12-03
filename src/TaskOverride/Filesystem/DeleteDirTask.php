<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\TaskOverride\Filesystem;

use Robo\Task\Filesystem\DeleteDir;

/**
 * @property iterable<string> $dirs
 */
class DeleteDirTask extends DeleteDir
{

    /**
     * @param iterable<string> $dirs
     */
    public function __construct(iterable $dirs = [])
    {
        parent::__construct($dirs);
    }

    /**
     * @return iterable<string>
     */
    public function getDirs(): iterable
    {
        return $this->dirs;
    }

    /**
     * @param iterable<string> $dirs
     */
    public function setDirs(iterable $dirs): static
    {
        $this->dirs = $dirs;

        return $this;
    }
}
