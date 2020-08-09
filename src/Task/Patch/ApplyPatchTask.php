<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\Patch;

use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Utils;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Symfony\Component\Filesystem\Filesystem;

class ApplyPatchTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use DownloadCurlTaskLoader;

    protected string $taskName = 'PMKR - Apply patch';

    protected Utils $utils;

    protected Filesystem $filesystem;

    public function __construct(
        Utils $utils,
        Filesystem $filesystem
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

    /**
     * @return $this
     */
    public function setPatch(?Patch $patch)
    {
        $this->patch = $patch;

        return $this;
    }
    // endregion

    public function setOptions(array $options)
    {
        parent::setOptions($options);
        if (array_key_exists('patch', $options)) {
            $this->setPatch($options['patch']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $patch = $this->getPatch();
        if ($patch) {
            $context['patch.key'] = $patch->key;
            $context['patch.versionConstraint'] = $patch->versionConstraint;
            $context['patch.issue'] = $patch->issue;
            $context['patch.description'] = $patch->description;
            $context['patch.uri'] = $patch->uri;
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo(
            'PMKR - Apply patch: {patch.uri}',
            $this->getTaskContext(),
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        // - IF it is remote THEN
        //   - download to cache.
        // - END
        // - IF it is remote THEN
        //   - copy from cache to destination.
        // - ELSE
        //   - copy from local to destination
        // - END
        // -
        // @todo Implement runDoIt() method.
        $patch = $this->getPatch();

        $srcUri = $patch->uri;
        $srcScheme = parse_url($srcUri, \PHP_URL_SCHEME) ?: 'file';
        $isRemote = $this->isRemote($srcScheme);

        $config = $this->getConfig();
        $cacheDir = $config->get('dir.cache') . '/file/patch';
        $cacheDst = "$cacheDir/" . implode('/', $patch->getConfigPath()) . '.patch';
        $srcDst = "$srcDir/{$library->name}";

        if ($isRemote) {
            $result = $this
                ->taskDownloadCurl()
                ->setOptions(['hashOptions' => $patch->checksum->jsonSerialize()])
                ->setUri($patch->uri)
                ->run();
        }

        return $this;
    }

    protected function isRemote(string $scheme): bool
    {
        return in_array($scheme, ['ftp', 'http', 'https']);
    }
}
