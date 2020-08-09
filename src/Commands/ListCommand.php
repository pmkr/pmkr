<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Symfony\Component\Finder\Finder;

class ListCommand extends CommandBase
{

    /**
     * List installed PHP versions.
     *
     * @command ls
     */
    public function local(
        array $options = [
            'format' => 'yaml',
        ]
    ): CommandResult {
        return CommandResult::dataWithExitCode($this->collectLocal(), 0);
    }

    protected function collectLocal(): array
    {
        $config = $this->getConfig();
        $srcDir = $config->get('dir.src');
        $dirs = (new Finder())
            ->in($srcDir)
            ->directories()
            ->name('php-*')
            ->depth(0);

        $instances = [];
        foreach ($dirs as $dir) {
            $instances[] = $dir->getPathname();
        }

        usort($instances, 'version_compare');

        return $instances;
    }
}
