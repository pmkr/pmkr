<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Library;

use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\TaskOverride\Archive\ExtractTaskLoader;
use Pmkr\Pmkr\Utils;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Symfony\Component\Filesystem\Filesystem;

class ArchiveDownloaderTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use DownloadCurlTaskLoader;
    use ExtractTaskLoader;
    use OptionsTrait;

    protected string $taskName = 'PMKR - Library download - Archive';

    protected Utils $utils;

    public function __construct(
        Utils $utils,
        Filesystem $filesystem
    ) {
        $this->utils = $utils;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $library = $this->getLibrary();
        $context['library.key'] = $library ? $library->key : '__unknown__';

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo(
            'PMKR - Library download - Archive - {library.key}',
            $this->getTaskContext(),
        );

        return $this;
    }

    public function setOptions(array $options)
    {
        parent::setOptions($options);
        $this->setOptionsCommon($options);

        return $this;
    }

    protected function runDoIt()
    {
        // @todo Apply patches.
        $config = $this->getConfig();
        $logger = $this->logger;
        $library = $this->getLibrary();
        $srcDir = $config->get('dir.src');

        $srcUrl = $library->downloader['options']['url'];
        $cacheDst = $this->utils->libraryCacheDestination($config, $srcUrl);
        $srcDst = "$srcDir/{$library->name}";

        $result = $this
            ->taskDownloadCurl()
            ->skipDownloadIfExists()
            ->setUri($srcUrl)
            ->setDestination($cacheDst)
            ->setHashOptions($library->downloader['options']['checksum'] ?? [])
            ->run();

        if (!$result->wasSuccessful()) {
            $logger->error(
                'Library {name} could not be downloaded from {srcUrl}',
                [
                    'name' => $library->name,
                    'srcUrl' => $srcUrl,
                ],
            );

            $this->taskResultCode = 2;
            $this->taskResultMessage = $result->getMessage();

            return $this;
        }

        $this->filesystem->remove($srcDst);
        $result = $this
            ->taskExtract()
            ->fileName($cacheDst)
            ->to($srcDst)
            ->run();

        if (!$result->wasSuccessful()) {
            $this->taskResultCode = 3;
            $this->taskResultMessage = $result->getMessage();
        }

        $child = $this->utils->getOnlyChildDir($srcDst);
        if (!$child) {
            return $this;
        }

        $this->filesystem->rename($child->getPathname(), "$srcDst-tmp");
        $this->filesystem->remove($srcDst);
        $this->filesystem->rename("$srcDst-tmp", $srcDst);

        return $this;
    }
}
