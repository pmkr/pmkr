<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task\PhpExtensionCompile;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pmkr\Pmkr\TaskOverride\Filesystem\DeleteDirTaskLoader;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\Data as RoboState;
use Robo\Task\Base\Tasks as ExecTaskLoader;
use Robo\TaskAccessor;
use Pmkr\Pmkr\Model\Extension;
use Pmkr\Pmkr\Task\BaseTask;
use Pmkr\Pmkr\Util\PhpExtensionCompileConfigureCommandBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PeclTask extends BaseTask implements BuilderAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;
    use ExecTaskLoader;
    use DeleteDirTaskLoader;
    use TaskLoader;

    protected string $taskName = 'PMKR extension:compile {extensionKey}';

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $extension = $this->getExtension();
        $context['extensionKey'] = $extension ? $extension->key : '__missing_extension_key__';

        return $context;
    }

    protected PhpExtensionCompileConfigureCommandBuilder $commandBuilder;

    protected Filesystem $filesystem;

    // region extensionSrcDir
    protected string $extensionSrcDir = '';

    public function getExtensionSrcDir(): string
    {
        return $this->extensionSrcDir;
    }

    /**
     * @return $this
     */
    public function setExtensionSrcDir(string $extensionSrcDir)
    {
        $this->extensionSrcDir = $extensionSrcDir;

        return $this;
    }
    // endregion

    // region extension
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
    // endregion

    // region phpBinDir
    protected string $phpBinDir = '';

    public function getPhpBinDir(): string
    {
        return $this->phpBinDir;
    }

    /**
     * @return $this
     */
    public function setPhpBinDir(string $phpBinDir)
    {
        $this->phpBinDir = $phpBinDir;

        return $this;
    }
    // endregion

    public function __construct(
        PhpExtensionCompileConfigureCommandBuilder $commandBuilder,
        Filesystem $filesystem
    ) {
        $this->commandBuilder = $commandBuilder;
        $this->filesystem = $filesystem;
    }

    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (array_key_exists('extensionSrcDir', $options)) {
            $this->setExtensionSrcDir($options['extensionSrcDir']);
        }

        if (array_key_exists('extension', $options)) {
            $this->setExtension($options['extension']);
        }

        if (array_key_exists('phpBinDir', $options)) {
            $this->setPhpBinDir($options['phpBinDir']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $extensionSrcDir = $this->getExtensionSrcDir();
        $extension = $this->getExtension();
        $phpBinDir = $this->getPhpBinDir();

        $cb = $this->collectionBuilder();
        $cb->addTask(
            $this
                ->taskPmkrBeforeExtensionConfigure()
                ->setExtensionSrcDir($extensionSrcDir)
                ->setExtension($extension)
                ->setPhpBinDir($phpBinDir)
        );

        $result = $cb
            ->addCode(function (RoboState $state) use ($extensionSrcDir): int {
                // There is no problem with the most of the extensions,
                // but some of the (phalcon, maxminddb) use a different directory
                // structure and the PHP extension is in a sub-directory.
                $state['extensionSrcDir'] = $extensionSrcDir;
                if ($this->filesystem->exists("$extensionSrcDir/config.m4")) {
                    return 0;
                }

                $files = (new Finder())
                    ->in($extensionSrcDir)
                    ->files()
                    ->name('config.m4')
                    ->depth('<2')
                    ->sort(function (\SplFileInfo $a, \SplFileInfo $b): int {
                        return mb_substr_count($a->getPathname(), \DIRECTORY_SEPARATOR)
                            <=> mb_substr_count($b->getPathname(), \DIRECTORY_SEPARATOR);
                    });

                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                $iterator = $files->getIterator();
                $iterator->rewind();
                $file = $iterator->valid() ? $iterator->current() : null;
                if (!$file) {
                    // This is a problem, but move forward.
                    return 0;
                }

                $state['extensionSrcDir'] = $file->getPath();

                return 0;
            })
            ->addTask(
                $this
                    ->taskExec('make clean || true')
                    ->deferTaskConfiguration('dir', 'extensionSrcDir')
            )
            ->addTask(
                $this
                    ->taskExec(escapeshellcmd("$phpBinDir/phpize"))
                    ->deferTaskConfiguration('dir', 'extensionSrcDir')
            )
            ->addTask(
                $this
                    ->taskExec($this->commandBuilder->build(
                        $extension,
                        $extensionSrcDir,
                        $phpBinDir,
                    ))
                    ->deferTaskConfiguration('dir', 'extensionSrcDir')
            )
            ->addTask(
                $this
                    ->taskExec('make -j$(nproc)')
                    ->deferTaskConfiguration('dir', 'extensionSrcDir')
            )
            ->addTask(
                $this
                    ->taskExec('make install')
                    ->deferTaskConfiguration('dir', 'extensionSrcDir')
            )
            ->run();

        if (!$result->wasSuccessful()) {
            throw new \Exception($result->getMessage(), $result->getExitCode());
        }

        return $this;
    }
}
