<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\Task\BaseTask;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

class InstallTask extends BaseTask implements
    BuilderAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use TaskLoader;
    use OptionsTrait;

    protected string $taskName = 'PMKR - Install library';

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            '{library.name}',
            [
                'library.name' => $this->getLibrary()->name,
            ],
        );

        return $this;
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

    protected function runDoIt(): static
    {
        $result = $this
            ->collectionBuilder()
            ->addTask(
                $this
                    ->taskPmkrLibraryDownloadWrapper()
                    ->setLibrary($this->getLibrary())
            )
            ->addTask(
                $this
                    ->taskPmkrLibraryCompilerWrapper()
                    ->setLibrary($this->getLibrary())
                    ->setSkipIfExists($this->getSkipIfExists())
            )
            ->run();

        if (!$result->wasSuccessful()) {
            throw new \RuntimeException($result->getMessage(), $result->getExitCode());
        }

        return $this;
    }
}
