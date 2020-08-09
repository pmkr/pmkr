<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\EtcDeploy;

use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Task\BaseTask;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment as TwigEnvironment;

abstract class BaseEtcDeployTask extends BaseTask
{

    protected TwigEnvironment $twig;

    protected Filesystem $filesystem;

    // region instance
    protected ?Instance $instance = null;

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    /**
     * @return $this
     */
    public function setInstance(?Instance $instance)
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    public function __construct(
        TwigEnvironment $twig,
        Filesystem $filesystem
    ) {
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    public function setOptions(array $options)
    {
        parent::setOptions($options);
        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        return $this;
    }

    abstract protected function getVars(array $etc, string $fileDefKey): array;

    protected function getVarsUname(): array
    {
        return [
            'uname' => [
                'a' => php_uname(),
                's' => php_uname('s'),
                'n' => php_uname('n'),
                'r' => php_uname('r'),
                'v' => php_uname('v'),
                'm' => php_uname('m'),
            ],
        ];
    }

    protected function deploy(array $etc, string $fileDefKey, array $fileDef)
    {
        $fileDef += [
            'enabled' => true,
            'dst' => $fileDefKey,
        ];

        if (empty($fileDef['enabled'])) {
            return $this;
        }

        $instance = $this->getInstance();
        $instanceShareDir = $instance->shareDir;

        $srcFileName = $fileDef['src'];
        $dstFileName = "$instanceShareDir/etc/{$fileDef['dst']}";

        try {
            $dstContent = $this->twig->render(
                $srcFileName,
                $this->getVars($etc, $fileDefKey),
            );
            $this->filesystem->mkdir(
                dirname($dstFileName),
                0777 - umask(),
            );
            $this->filesystem->dumpFile($dstFileName, $dstContent);
        } catch (\Exception $e) {
            $this->taskResultCode = 1;
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
