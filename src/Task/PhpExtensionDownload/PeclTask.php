<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionDownload;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\NoResponseCode;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Sweetchuck\PearClient\PearClientInterface;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\TaskOverride\Archive\ExtractTaskLoader;
use Pmkr\Pmkr\Util\PhpExtensionVersionDetector;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Symfony\Component\Filesystem\Filesystem;

class PeclTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use DownloadCurlTaskLoader;
    use ExtractTaskLoader;

    protected string $taskName = 'PMKR - PHP extension download - PECL';

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        if ($extension) {
            $context['extension.key'] = $extension->key;
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo('{extension.key}');

        return $this;
    }

    protected PhpExtensionVersionDetector $extVersionDetector;

    protected PearClientInterface $peclClient;

    protected Utils $utils;

    protected Filesystem $filesystem;

    public function __construct(
        PhpExtensionVersionDetector $extensionVersionDetector,
        PearClientInterface $peclClient,
        Utils $utils,
        Filesystem $filesystem
    ) {
        $this->extVersionDetector = $extensionVersionDetector;
        $this->peclClient = $peclClient;
        $this->utils = $utils;
        $this->filesystem = $filesystem;
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

    public function getExtension(): ?Extension
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

    protected function runDoIt()
    {
        // @todo Apply patches.
        $config = $this->getConfig();
        $instance = $this->getInstance();
        $extension = $this->getExtension();
        $instanceSrcDir = $instance->srcDir;
        $extName = $extension->name;
        $extDir = "$instanceSrcDir/ext/$extName";
        $extVersionInstalled = $this->extVersionDetector->detect(
            $instance->coreVersionNumber,
            $extDir,
            $extName,
        );
        $isCoreExtension = $extVersionInstalled === 'PHP_VERSION';
        $logger = $this->logger;
        if ($isCoreExtension) {
            $logger->info(
                'Extension {extensionName} is a core extension no need to download',
                [
                    'extensionName' => $extName,
                ],
            );

            return $this;
        }

        $extVersionAsRequired = $extension['version'];
        try {
            $releases = $this->getReleases();
        } catch (NoResponseCode $e) {
            $message = $e->getMessage();
            $messageArgs = [];
            $logger->error($message, $messageArgs);

            $this->taskResultCode = 2;
            $this->taskResultMessage = strtr($message, $messageArgs);

            return $this;
        }
        $release = $this->utils->pickPearReleaseVersion($extVersionAsRequired, $releases);
        if (!$release) {
            $message = 'PHP extension {extensionName} required as {extensionVersion} version is not available.';
            $messageArgs = [
                'extensionName' => $extName,
                'extensionVersion' => $extVersionAsRequired,
            ];

            $logger->error($message, $messageArgs);
            $this->taskResultCode = 1;
            $this->taskResultMessage = strtr($message, $messageArgs);

            return $this;
        }

        $downloadSrc = $this->utils->phpExtensionPeclDownloadUri($extName, $release->version);
        $downloadDst = $this->utils->phpExtensionCacheDestination($config, $downloadSrc);
        $result = $this
            ->taskDownloadCurl()
            ->skipDownloadIfExists()
            ->setUri($downloadSrc)
            ->setDestination($downloadDst)
            ->setHashOptions($extension['checksum'] ?? [])
            ->run();
        if (!$result->wasSuccessful()) {
            $logger->error(
                'Extension {extensionName}:{extensionVersionAsRequired} could not be downloaded from {downloadSrc}',
                [
                    'extensionName' => $extName,
                    'extensionVersionAsRequired' => $extVersionAsRequired,
                    'downloadSrc' => $downloadSrc,
                ],
            );

            $this->taskResultCode = 2;
            $this->taskResultMessage = $result->getMessage();

            return $this;
        }

        $this->filesystem->remove($extDir);
        $result = $this
            ->taskExtract()
            ->fileName($downloadDst)
            ->to($extDir)
            ->run();
        if (!$result->wasSuccessful()) {
            $this->taskResultCode = 3;
            $this->taskResultMessage = $result->getMessage();

            return $this;
        }

        $extVersion = $release->version;
        $hasPackageXml = $this->filesystem->exists("$extDir/package.xml");
        $hasSubDir = $this->filesystem->exists("$extDir/$extName-$extVersion");
        if ($hasPackageXml && $hasSubDir) {
            $this->filesystem->rename(
                "$extDir/$extName-$extVersion",
                dirname($extDir) . "/$extName-$extVersion",
            );
            $this->filesystem->remove($extDir);
            $this->filesystem->rename(
                dirname($extDir) . "/$extName-$extVersion",
                $extDir,
            );
        }

        return $this;
    }

    protected function getReleases(): array
    {
        $extension = $this->getExtension();

        // @todo Error handling.
        return $this->peclClient->packageAllReleasesGet($extension['name'])->list;
    }
}
