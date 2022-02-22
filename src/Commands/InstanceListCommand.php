<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pmkr\Pmkr\Instance\InstanceComparer;
use Pmkr\Pmkr\Model\Instance;
use Symfony\Component\Yaml\Yaml;

class InstanceListCommand extends CommandBase
{

    /**
     * Lists all instances which are defined any pmkr.*.yml files.
     *
     * @param mixed[] $options
     *
     * @command instance:list
     *
     * @option string $fields
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
            'filter' => '',
            'fields' => '',
        ]
    ): CommandResult {
        $instances = iterator_to_array(
            $this->getPmkr()->instances->getIterator(),
        );

        if ($options['filter'] !== '') {
            $filter = $this->getContainer()->get('pmkr.instance.filter');
            $filter->setOptions(Yaml::parse($options['filter']));
            $instances = array_filter($instances, $filter);
        }

        return CommandResult::data($instances);
    }

    /**
     * @param mixed $result
     *
     * @hook process @pmkrProcessInstanceHiddenFilter
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceHiddenFilter($result, CommandData $commandData): void
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
     * @param mixed $result
     *
     * @hook process @pmkrProcessInstanceOrderBy
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceOrderBy($result, CommandData $commandData): void
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
     * @param mixed $result
     *
     * @hook process @pmkrProcessInstanceFormat
     *
     * @link https://github.com/consolidation/annotated-command#process-hook
     */
    public function onHookProcessPmkrInstanceFormat($result, CommandData $commandData): void
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

        if (in_array($format, ['list', 'table'])) {
            $rows = new RowsOfFields($rows);
        }

        $result->setOutputData($rows);
    }
}
