<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Pmkr\Pmkr\Util\EnvPathHandler;

class WhichCommand extends CommandBase
{
    protected EnvPathHandler $envPathHandler;

    protected function initDependencies(): static
    {
        if ($this->initialized) {
            return $this;
        }

        parent::initDependencies();
        $container = $this->getContainer();
        $this->envPathHandler = $container->get('pmkr.env_path.handler');

        return $this;
    }

    /**
     * Shows the currently used PHP instance with the environment variables.
     *
     * @param mixed[] $options
     *
     * @command which
     *
     * @pmkrInitNormalizeConfig
     */
    public function cmdWhichExecute(
        array $options = [
            'format' => 'yaml',
        ],
    ): ?CommandResult {
        $envVars = $this->getConfig()->get('env');

        $instanceName = $this->envPathHandler->getCurrentInstanceName($envVars['PATH'] ?? '');
        if ($instanceName === null) {
            return null;
        }

        $pmkr = $this->getPmkr();
        $data = [
            'instanceName' => $instanceName,
            'shareDir' => $pmkr->instances[$instanceName]->shareDir,
        ];

        if (isset($envVars['PMKR_WRAPPER_PHPRC'])) {
            $data['PHPRC'] = $envVars['PMKR_WRAPPER_PHPRC'];
        }

        if (isset($envVars['PMKR_WRAPPER_PHP_INI_SCAN_DIR'])) {
            $data['PHP_INI_SCAN_DIR'] = $envVars['PMKR_WRAPPER_PHP_INI_SCAN_DIR'];
        }

        return CommandResult::data(
            array_filter($data, '\strlen'),
        );
    }
}
