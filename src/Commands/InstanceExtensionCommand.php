<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class InstanceExtensionCommand extends CommandBase
{

    /**
     * @command instance:extension:list
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName arg.instanceName
     * @pmkrValidateResolveInstanceAlias arg.instanceName
     * @pmkrValidateInstanceName arg.instanceName
     */
    public function cmdListExecute(string $instanceName): CommandResult
    {
        $pmkr = $this->getPmkr();
        $instance = $pmkr->instances[$instanceName];
        $extensionsAll = iterator_to_array($pmkr->extensions->getIterator());
        /** @var \Pmkr\Pmkr\Model\ExtensionSetItem[] $extensionSetItems */
        $extensionSetItems = iterator_to_array($instance->extensionSet->getIterator());
        /** @var \Pmkr\Pmkr\Model\Extension[] $extensions */
        $extensions = array_intersect_key($extensionsAll, $extensionSetItems);
        $rows = [];
        foreach ($extensions as $extKey => $extension) {
            $rows[$extKey] = [
                'key' => $extKey,
                'name' => $extension->name,
                'status' => $extensionSetItems[$extKey]->status,
            ];
        }

        return CommandResult::data(new RowsOfFields($rows));
    }
}
