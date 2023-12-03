<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\Task\BaseTask;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Symfony\Component\Filesystem\Filesystem;

class CompilerWrapperTask extends BaseTask implements
    ContainerAwareInterface,
    BuilderAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use TaskLoader;
    use OptionsTrait;

    protected string $taskName = 'PMKR - Library compiler wrapper';

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);

        $library = $this->getLibrary();
        if ($library) {
            $context['library.name'] = $library->name;
        }

        return $context;
    }

    protected function runHeader(): static
    {
        $message = $this->isSkipped() ?
            'SKIP library compile: {library.name}'
            : 'Library compiler wrapper: {library.name}';

        $this->printTaskInfo(
            $message,
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
        if ($this->isSkipped()) {
            return $this;
        }

        $library = $this->getLibrary();
        switch ($library->compiler['type']) {
            case 'common':
                $task = $this->taskPmkrLibraryCompilerCommon();
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
