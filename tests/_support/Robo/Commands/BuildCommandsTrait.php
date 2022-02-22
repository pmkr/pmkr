<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Robo\Commands;

use Robo\Contract\TaskInterface;
use Robo\Task\Base\Tasks as ExecTaskLoader;

trait BuildCommandsTrait
{
    use ExecTaskLoader;

    /**
     * @param \Robo\Symfony\ConsoleIO $io
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    abstract protected function collectionBuilder($io = null);

    /**
     * @command build:image
     */
    public function cmdBuildImageExecute(): TaskInterface
    {
        return $this->getTaskBuildImage();
    }

    protected function getTaskBuildImage(): TaskInterface
    {
        $inkscapeExecutable = 'inkscape';
        $inkscapeExecutableSafe = escapeshellcmd($inkscapeExecutable);

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'open-graph.png' => $this->taskExec(sprintf(
                    '%s --export-overwrite --export-filename=%s %s',
                    $inkscapeExecutableSafe,
                    escapeshellcmd('./resources/images/open-graph.png'),
                    escapeshellcmd('./resources/images/open-graph.svg'),
                )),
                'icon-square.png' => $this->taskExec(sprintf(
                    '%s --export-overwrite --export-filename=%s %s',
                    $inkscapeExecutableSafe,
                    escapeshellcmd('./resources/images/icon-square.png'),
                    escapeshellcmd('./resources/images/icon.svg'),
                )),
                'logo.png' => $this->taskExec(sprintf(
                    '%s --export-overwrite --export-area-drawing --export-filename=%s %s',
                    $inkscapeExecutableSafe,
                    escapeshellcmd('./resources/images/logo.png'),
                    escapeshellcmd('./resources/images/icon.svg'),
                )),
            ]);
    }
}
