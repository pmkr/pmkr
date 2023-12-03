<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\TaskOverride\Filesystem\DeleteDirTaskLoader;
use Pmkr\Pmkr\Utils;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\Task\Vcs\Tasks as GitTaskLoader;
use Robo\TaskAccessor;
use Symfony\Component\Filesystem\Filesystem;

class GitDownloaderTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use GitTaskLoader;
    use DeleteDirTaskLoader;
    use OptionsTrait;

    protected Utils $utils;

    protected string $srcUrl = '';

    protected string $cacheDst = '';

    protected string $shareDst = '';

    public function __construct(Utils $utils, Filesystem $fs)
    {
        $this->utils = $utils;
        $this->filesystem = $fs;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);
        $this->setOptionsCommon($options);

        return $this;
    }

    protected function runHeader(): static
    {
        $library = $this->getLibrary();
        $url = $library->downloader['options']['url'];

        $this->printTaskInfo(
            'PMKR - Git clone {src} into {dst}',
            [
                'src' => $url,
                'dst' => $this->utils->gitUrlToCacheDestination($this->getConfig(), $url),
            ],
        );

        return $this;
    }

    protected function runDoIt(): static
    {
        $config = $this->getConfig();
        $library = $this->getLibrary();
        $shareDir = $config->get('dir.share');

        $this->srcUrl = $library->downloader['options']['url'];
        $this->cacheDst = $this->utils->gitUrlToCacheDestination($config, $this->srcUrl);
        $this->shareDst = "$shareDir/{$library->name}";

        $cb = $this->collectionBuilder();
        $cb->addTask($this->taskDeleteDir([$this->shareDst]));

        if (!$this->filesystem->exists($this->cacheDst)) {
            $cb->addTask($this->getTaskCacheInit());
        } else {
            $cb->addTask($this->getTaskCacheUpdate());
        }

        $cb->addTask($this->getTaskCloneCacheToDst());

        $result = $cb->run();
        if (!$result->wasSuccessful()) {
            $this->taskResultCode = 1;
            $this->taskResultMessage = $result->getMessage();
        }

        return $this;
    }

    protected function getTaskCacheInit(): TaskInterface
    {
        return $this
            ->taskGitStack()
            ->exec(sprintf(
                'clone --bare --mirror %s %s',
                escapeshellarg($this->srcUrl),
                escapeshellarg($this->cacheDst),
            ));
    }

    protected function getTaskCacheUpdate(): TaskInterface
    {
        $library = $this->getLibrary();
        $branch = $library->downloader['options']['branch'];

        return $this
            ->taskGitStack()
            ->dir($this->cacheDst)
            ->exec(sprintf(
                'fetch origin %s',
                escapeshellarg("$branch:$branch-tmp"),
            ))
            ->exec(sprintf(
                'branch --delete %s',
                escapeshellarg($branch),
            ))
            ->exec(sprintf(
                'branch --move %s %s',
                escapeshellarg("$branch-tmp"),
                escapeshellarg($branch),
            ));
    }

    /**
     * @return \Robo\Contract\TaskInterface
     */
    protected function getTaskCloneCacheToDst()
    {
        $library = $this->getLibrary();
        $options = $library->downloader['options'];

        $ref = match ($options['refType']) {
            'tag' => 'refs/tags/' . $options['refValue'],
            'branch' => 'refs/heads/' . $options['refValue'],
            default => $options['refValue'],
        };

        return $this
            ->taskGitStack()
            ->exec(sprintf(
                'clone --recurse-submodules %s %s',
                escapeshellarg($this->cacheDst),
                escapeshellarg($this->shareDst),
            ))
            ->exec(sprintf(
                '--git-dir %s checkout %s',
                escapeshellarg("$this->shareDst/.git"),
                escapeshellarg($ref),
            ));
    }
}
