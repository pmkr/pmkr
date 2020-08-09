<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\Util\TemplateHelper;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\Task\File\Tasks as FileTaskLoader;
use Robo\Task\Filesystem\Tasks as FilesystemTaskLoader;

class InitCommand extends CommandBase
{
    use FileTaskLoader;
    use FilesystemTaskLoader;

    protected TemplateHelper $templateHelper;

    protected function initDependencies()
    {
        if ($this->initialized) {
            return $this;
        }

        parent::initDependencies();
        $container = $this->getContainer();
        $this->templateHelper = $container->get('pmkr.template_helper');

        return $this;
    }

    /**
     * @command init
     */
    public function cmdInitExecute(
        array $options = [
            'phpBinary' => '',
            'force' => false,
        ]
    ): TaskInterface {
        $cb = $this->collectionBuilder();
        $this
            ->addTasksInitPmkr($cb, $options)
            ->addTasksInitHome($cb, $options);

        return $cb;
    }

    /**
     * @command init:home
     */
    public function cmdInitHomeExecute(
        array $options = [
            'force' => false,
        ]
    ): TaskInterface {
        $cb = $this->collectionBuilder();
        $this->addTasksInitHome($cb, $options);

        return $cb;
    }

    /**
     * Initializes ~/bin/pmkr to make sure that pmkr always uses a specific PHP
     * executable.
     *
     * @command init:pmkr
     */
    public function cmdInitPmkrExecute(
        array $options = [
            'phpBinary' => '',
            'force' => false,
            'format' => 'code',
        ]
    ): CollectionBuilder {
        $cb = $this->collectionBuilder();
        $this->addTasksInitPmkr($cb, $options);

        return $cb;
    }

    protected function addTasksInitHome(CollectionBuilder $cb, array $options)
    {
        $cb->addCode(
            function () use ($options): int {
                $files = $this->getContainer()->get('finder');
                $files
                    ->in($this->utils->getPmkrRoot() . '/resources/home')
                    ->files();

                $config = $this->getConfig();
                $dstDir = $config->get('env.HOME') . '/.' . $config->get('app.name');
                $dirUmask = 0777 - umask();
                foreach ($files as $file) {
                    $dst = "$dstDir/" . $file->getRelativePathname();
                    $action = 'copy';
                    $isDstExists = $this->fs->exists($dst);
                    if ($isDstExists) {
                        $action = $options['force'] ? 'overwrite' : 'skip';
                    }

                    $this->logger->info(
                        'init {action}: {src} to {dst}',
                        [
                            'action' => $action,
                            'src' => $file->getPathname(),
                            'dst' => $dst,
                        ],
                    );

                    if ($action === 'skip') {
                        continue;
                    }

                    $this->fs->mkdir($file->getPath(), $dirUmask);
                    $this->fs->copy($file->getPathname(), $dst, true);
                }

                return 0;
            }
        );

        return $this;
    }

    protected function addTasksInitPmkr(CollectionBuilder $cb, array $options)
    {
        $cb->addCode(
            function () use ($options): int {
                $fileName = $this->getConfig()->get('env.HOME') . '/bin/pmkr';
                if ($this->fs->exists($fileName) && empty($options['force'])) {
                    $this->logger->info(
                        'File {fileName} already exists',
                        [
                            'fileName' => $fileName,
                        ]
                    );

                    return 0;
                }

                $context = [];
                if (!empty($options['phpBinary'])) {
                    $context['phpBinary'] = $options['phpBinary'];
                }
                $fileContent = $this->templateHelper->renderExamplePmkr($context);
                $result = $this
                    ->taskWriteToFile('')
                    ->filename($fileName)
                    ->text($fileContent)
                    ->run();

                if (!$result->wasSuccessful()) {
                    return 1;
                }

                $result = $this
                    ->taskFilesystemStack()
                    ->chmod($fileName, 0777 - umask())
                    ->run();

                return $result->wasSuccessful() ? 0 : 1;
            }
        );

        return $this;
    }
}
