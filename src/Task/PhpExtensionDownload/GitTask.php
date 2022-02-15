<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionDownload;

use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\TaskOverride\Filesystem\DeleteDirTaskLoader;
use Pmkr\Pmkr\Utils;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\Task\Vcs\Tasks as GitTaskLoader;
use Robo\TaskAccessor;
use Symfony\Component\Filesystem\Filesystem;

class GitTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use GitTaskLoader;
    use DeleteDirTaskLoader;

    protected Utils $utils;

    protected Filesystem $fs;

    public function __construct(Utils $utils, Filesystem $fs)
    {
        $this->utils = $utils;
        $this->fs = $fs;
    }

    //region instance
    protected ?Instance $instance = null;

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    /**
     * @return $this
     */
    public function setInstance(Instance $instance)
    {
        $this->instance = $instance;

        return $this;
    }
    //endregion

    //region extension
    protected ?Extension $extension = null;

    public function getExtension(): Extension
    {
        return $this->extension;
    }

    /**
     * @return $this
     */
    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;

        return $this;
    }
    //endregion

    protected string $cacheDst = '';

    protected string $extDst = '';

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        return $this;
    }

    protected function runHeader()
    {
        $url = $this->getExtension()->downloader->options['url'];

        $this->printTaskInfo(
            'PMKR - Git clone {src} into {dst}',
            [
                'src' => $url,
                'dst' => $this->utils->gitUrlToCacheDestination($this->getConfig(), $url),
            ],
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $config = $this->getConfig();
        $instance = $this->getInstance();
        $extension = $this->getExtension();

        $this->cacheDst = $this->utils->gitUrlToCacheDestination($config, $extension->downloader->options['url']);
        $this->extDst = $instance->srcDir . '/ext/' . $extension->name;

        $cb = $this->collectionBuilder();
        $cb->addTask($this->taskDeleteDir([$this->extDst]));

        if (!$this->fs->exists($this->cacheDst)) {
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
                escapeshellarg($this->getExtension()->downloader->options['url']),
                escapeshellarg($this->cacheDst),
            ));
    }

    protected function getTaskCacheUpdate(): TaskInterface
    {
        $extension = $this->getExtension();
        $branch = $extension->downloader->options['branch'];

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

    protected function getTaskCloneCacheToDst(): TaskInterface
    {
        $extension = $this->getExtension();
        $options = $extension->downloader->options;

        switch ($options['refType']) {
            case 'tag':
                $ref = 'refs/tags/' . $options['refValue'];
                break;

            case 'branch':
                $ref = 'refs/heads/' . $options['refValue'];
                break;

            default:
                $ref = $options['refValue'];
                break;
        }

        return $this
            ->taskGitStack()
            ->exec(sprintf(
                'clone --recurse-submodules %s %s',
                escapeshellarg($this->cacheDst),
                escapeshellarg($this->extDst),
            ))
            ->exec(sprintf(
                '--git-dir %s checkout %s',
                escapeshellarg("$this->extDst/.git"),
                escapeshellarg($ref),
            ));
    }
}
