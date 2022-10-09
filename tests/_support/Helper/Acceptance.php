<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Helper;

use Codeception\Module;

class Acceptance extends Module
{
    public function grabPmkrExecutable(): string
    {
        return $this->_getConfig('pmkrExecutable') ?: './bin/pmkr';
    }
}
