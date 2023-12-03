<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pmkr\Pmkr\Model\Library;
use Robo\Contract\TaskInterface;

class LibraryCommand extends CommandBase
{

    /**
     * @param mixed[] $options
     *
     * @command library:list
     *
     * @aliases lls
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdLibraryListExecute(
        array $options = [
            'format' => 'table',
        ],
    ): CommandResult {
        return CommandResult::data(
            iterator_to_array($this->getPmkr()->libraries->getIterator()),
        );
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     *
     * @hook process library:list
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function cmdLibraryListProcess(
        $result,
        CommandData $commandData,
    ) {
        if (!($result instanceof CommandResult)) {
            return $result;
        }

        /** @var \Pmkr\Pmkr\Model\Library[] $libraries */
        $libraries = $result->getOutputData();
        $library = reset($libraries);
        if (!$library) {
            return $result;
        }

        if (!($library instanceof Library)) {
            return $result;
        }

        $input = $commandData->input();
        $format = $input->getOption('format');
        $isHuman = $this->isHumanReadableOutputFormat($format);

        $converter = $this->getContainer()->get('pmkr.library.command_result_converter');
        $rows = $converter->toFlatRows($libraries, $isHuman);

        if ($format === 'table') {
            $rows = new RowsOfFields($rows);
        }

        $result->setOutputData($rows);

        return $result;
    }

    /**
     * Download and compile together.
     *
     * @param mixed[] $options
     *
     * @command library:install
     *
     * @aliases li
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdLibraryInstallExecute(
        string $libraryKey,
        array $options = [
            'skipIfExists' => true,
        ],
    ): TaskInterface {
        $pmkr = $this->getPmkr();
        $library = $pmkr->libraries[$libraryKey];

        return $this
            ->taskPmkrLibraryInstall()
            ->setLibrary($library)
            ->setSkipIfExists($options['skipIfExists']);
    }

    /**
     * @command library:download
     *
     * @aliases ld
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdLibraryDownloadExecute(string $libraryKey): TaskInterface
    {
        $pmkr = $this->getPmkr();
        $library = $pmkr->libraries[$libraryKey];

        return $this
            ->taskPmkrLibraryDownloadWrapper()
            ->setLibrary($library);
    }

    /**
     * @command library:compile
     *
     * @aliases lc
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdLibraryCompileExecute(string $libraryKey): TaskInterface
    {
        $pmkr = $this->getPmkr();
        $library = $pmkr->libraries[$libraryKey];

        return $this
            ->taskPmkrLibraryCompilerWrapper()
            ->setSkipIfExists(false)
            ->setLibrary($library);
    }
}
