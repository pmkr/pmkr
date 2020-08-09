<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pmkr\Pmkr\Model\Library;

class LibraryCommand extends CommandBase
{

    /**
     * @command library:list
     *
     * @aliases lls
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdLibraryListExecute(
        array $options = [
            'format' => 'table',
        ]
    ): CommandResult {
        return CommandResult::data(
            iterator_to_array($this->getPmkr()->libraries->getIterator()),
        );
    }

    /**
     * @hook process library:list
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function cmdLibraryListProcess(
        $result,
        CommandData $commandData
    ) {
        if (!($result instanceof CommandResult)) {
            return $result;
        }

        $libraries = $result->getOutputData();
        if (!$libraries) {
            return $result;
        }

        $library = reset($libraries);
        if (!($library instanceof Library)) {
            return $result;
        }

        $input = $commandData->input();
        $format = $input->getOption('format');
        $isHuman = in_array($format, ['table']);

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
        ]
    ) {
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
    public function cmdLibraryDownloadExecute(
        string $libraryKey,
        array $options = []
    ) {
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
    public function cmdLibraryCompileExecute(
        string $libraryKey,
        array $options = []
    ) {
        $pmkr = $this->getPmkr();
        $library = $pmkr->libraries[$libraryKey];

        return $this
            ->taskPmkrLibraryCompilerWrapper()
            ->setSkipIfExists(false)
            ->setLibrary($library);
    }
}
