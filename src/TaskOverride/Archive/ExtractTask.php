<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\TaskOverride\Archive;

use Robo\Task\Archive\Extract as ExtractTaskBase;

/**
 * @link https://github.com/consolidation/robo/issues/1078
 */
class ExtractTask extends ExtractTaskBase
{

    public function fileName(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function __construct(string $filename = '')
    {
        parent::__construct($filename);
    }
}
