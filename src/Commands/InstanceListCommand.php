<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pmkr\Pmkr\Instance\InstanceComparer;
use Pmkr\Pmkr\Model\Instance;

class InstanceListCommand extends CommandBase
{

    /**
     * Lists all instances which are defined any pmkr.*.yml files.
     *
     * @command instance:list
     *
     * @aliases ils
     *
     * @pmkrInitNormalizeConfig
     * @pmkrProcessInstanceHiddenFilter
     * @pmkrProcessInstanceOrderBy
     * @pmkrProcessInstanceFormat
     */
    public function cmdInstanceListExecute(
        array $options = [
            'show-hidden' => false,
            'format' => 'table',
        ]
    ): CommandResult {
        return CommandResult::data(iterator_to_array(
            $this->getPmkr()->instances->getIterator(),
        ));
    }

    /**
     * @hook process @pmkrProcessInstanceHiddenFilter
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceHiddenFilter($result, CommandData $commandData)
    {
        if (!($result instanceof CommandResult)) {
            return;
        }

        $instances = $result->getOutputData();
        /** @var \Pmkr\Pmkr\Model\Instance $instance */
        $instance = reset($instances);
        if (!($instance instanceof Instance)) {
            return;
        }

        $input = $commandData->input();
        $showHidden = $input->hasOption('show-hidden') && $input->getOption('show-hidden');
        if ($showHidden) {
            return;
        }

        $instances = array_filter(
            $instances,
            function (Instance $instance): bool {
                return !$instance->hidden;
            },
        );

        $result->setOutputData($instances);
    }

    /**
     * @hook process @pmkrProcessInstanceOrderBy
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceOrderBy($result, CommandData $commandData)
    {
        if (!($result instanceof CommandResult)) {
            return;
        }

        $instances = $result->getOutputData();
        /** @var \Pmkr\Pmkr\Model\Instance $instance */
        $instance = reset($instances);
        if (!($instance instanceof Instance)) {
            return;
        }

        // @todo Service.
        $comparer = new InstanceComparer();
        $comparer->setKeys([
            'coreVersion' => [
                'comparer' => 'version_compare',
            ],
            'key' => [],
        ]);

        uasort($instances, $comparer);
        $result->setOutputData($instances);
    }

    /**
     * @hook process @pmkrProcessInstanceFormat
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceFormat($result, CommandData $commandData)
    {
        if (!($result instanceof CommandResult)) {
            return;
        }

        $instances = $result->getOutputData();
        /** @var \Pmkr\Pmkr\Model\Instance $instance */
        $instance = reset($instances);
        if (!($instance instanceof Instance)) {
            return;
        }

        $format = $commandData->input()->getOption('format');
        $isHuman = in_array($format, ['table']);

        $converter = $this->getContainer()->get('pmkr.instance.command_result_converter');
        $rows = $converter->toFlatRows($instances, $isHuman);

        if ($format === 'table') {
            $rows = new RowsOfFields($rows);
        }

        $result->setOutputData($rows);
    }
}
