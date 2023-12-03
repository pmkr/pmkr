<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Patch;

use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Robo\Contract\BuilderAwareInterface;
use Robo\Task\Base\Tasks as BaseTaskLoader;
use Robo\TaskAccessor;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Symfony\Component\Filesystem\Filesystem;

class ApplyPatchTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use DownloadCurlTaskLoader;
    use BaseTaskLoader;

    protected string $taskName = 'PMKR - Apply patch';

    protected Utils $utils;

    protected Filesystem $filesystem;

    public function __construct(
        Utils $utils,
        Filesystem $filesystem,
    ) {
        $this->utils = $utils;
        $this->filesystem = $filesystem;
    }

    // region patch
    protected ?Patch $patch = null;

    public function getPatch(): ?Patch
    {
        return $this->patch;
    }

    public function setPatch(?Patch $patch): static
    {
        $this->patch = $patch;

        return $this;
    }
    // endregion

    // region srcDir
    protected string $srcDir = '';

    public function getSrcDir(): string
    {
        return $this->srcDir;
    }

    public function setSrcDir(string $srcDir): static
    {
        $this->srcDir = $srcDir;

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);
        if (array_key_exists('patch', $options)) {
            $this->setPatch($options['patch']);
        }

        if (array_key_exists('srcDir', $options)) {
            $this->setSrcDir($options['srcDir']);
        }

        return $this;
    }

    /**
     *
     * @param ?array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $patch = $this->getPatch();
        if ($patch) {
            $context['patch.key'] = $patch->key;
            $context['patch.when.versionConstraint'] = $patch->when['versionConstraint'] ?? '';
            $context['patch.issue'] = $patch->issue;
            $context['patch.description'] = $patch->description;
            $context['patch.uri'] = $patch->uri;
        }
        $context['srcDir'] = $this->getSrcDir();

        return $context;
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo(
            'PMKR - Apply patch: {patch.key} {patch.uri} to {srcDir}',
            $this->getTaskContext(),
        );

        return $this;
    }

    protected function runDoIt(): static
    {
        $srcDir = $this->getSrcDir();
        $patch = $this->getPatch();
        $patchSrcFileName = $patch->uri;
        $config = $this->getConfig();

        if (!stream_is_local($patchSrcFileName)) {
            $patchCacheFileName = $config->get('dir.cache') . "/file/patch/{$patch->key}.patch";
            $result = $this
                ->taskDownloadCurl()
                ->setOptions(['hashOptions' => $patch->checksum->jsonSerialize()])
                ->setUri($patch->uri)
                ->setDestination($patchCacheFileName)
                ->run();

            if (!$result->wasSuccessful()) {
                $this->taskResultCode = max($result->getExitCode(), 1);
                $this->taskResultMessage = $result->getMessage();

                return $this;
            }

            $patchSrcFileName = $patchCacheFileName;
        }

        $command = sprintf(
            'patch -p1 < %s',
            escapeshellarg($patchSrcFileName),
        );

        $result = $this
            ->taskExec($command)
            ->dir($srcDir)
            ->run();

        if (!$result->wasSuccessful()) {
            $this->taskResultCode = $result->getExitCode();
            $this->taskResultMessage = $result->getMessage();
        }

        return $this;
    }
}
