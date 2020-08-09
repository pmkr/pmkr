<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\TaskOverride\Filesystem;

use Robo\Task\Filesystem\DeleteDir;

class DeleteDirTask extends DeleteDir
{

    public function __construct(iterable $dirs = [])
    {
        parent::__construct($dirs);
    }

    public function getDirs(): iterable
    {
        return $this->dirs;
    }

    public function setDirs(iterable $dirs)
    {
        $this->dirs = $dirs;

        return $this;
    }
}
