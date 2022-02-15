<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\Task\BaseTask;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

class DownloaderWrapperTask extends BaseTask implements
    ContainerAwareInterface,
    BuilderAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use TaskLoader;
    use OptionsTrait;

    protected string $taskName = 'PMKR - Library download wrapper: {name}';

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        $this->setOptionsCommon($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $library = $this->getLibrary();
        switch ($library->downloader['type']) {
            case 'archive':
                $task = $this->taskPmkrLibraryDownloadArchive();
                $task->setLibrary($library);
                break;

            case 'git':
                $task = $this->taskPmkrLibraryDownloadGit();
                $task->setLibrary($library);
                break;

            default:
                throw new \Exception('not implemented');
        }

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            $this->taskResultCode = $result->getExitCode() ?: 1;
            $this->taskResultMessage = $result->getMessage();
        }

        return $this;
    }
}
