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

    public function setInstance(?Instance $instance): static
    {
        $this->instance = $instance;

        return $this;
    }
    // endregion

    public function __construct(
        TwigEnvironment $twig,
        Filesystem $filesystem,
    ) {
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);
        if (array_key_exists('instance', $options)) {
            $this->setInstance($options['instance']);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $etc
     *
     * @return array<string, mixed>
     */
    abstract protected function getVars(array $etc, string $fileDefKey): array;

    /**
     * @return array{
     *     uname: array{
     *         a: string,
     *         s: string,
     *         n: string,
     *         r: string,
     *         v: string,
     *         m: string,
     *     },
     * }
     */
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

    /**
     * @param array<string, mixed> $etc
     * @param string $fileDefKey
     * @param array{
     *     enabled?: bool,
     *     src: string,
     *     dst?: string,
     * } $fileDef
     */
    protected function deploy(array $etc, string $fileDefKey, array $fileDef): static
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

    protected function getExtFileSuffix(Instance $instance): string
    {
        return version_compare($instance->coreVersion, '7.2', '<') ? '.so' : '';
    }
}
