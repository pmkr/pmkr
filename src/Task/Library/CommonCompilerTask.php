<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use Pmkr\Pmkr\Model\Library;
use Pmkr\Pmkr\Task\BaseTask;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Robo\Task\Base\Tasks as BaseTaskLoader;

class CommonCompilerTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use BaseTaskLoader;
    use OptionsTrait;

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $library = $this->getLibrary();
        $config = $this->getConfig();
        $srcDir = $config->get('dir.src') . "/$library->name";
        $dstDir = $config->get('dir.share') . "/$library->name";

        $result = $this
            ->taskExecStack()
            ->dir($srcDir)
            ->envVars([
                'prefix' => $dstDir,
                'srcDir' => $srcDir,
                'libraryKey' => $library->key,
                'libraryName' => $library->name,
            ])
            ->exec($library->compiler['options']['exec'])
            ->run();

        if (!$result->wasSuccessful()) {
            throw new \RuntimeException($result->getMessage(), 1);
        }

        return $this;
    }
}
