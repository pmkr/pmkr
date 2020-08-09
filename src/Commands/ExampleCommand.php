<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Pmkr\Pmkr\CodeResult\CodeCommandResult;
use Pmkr\Pmkr\CodeResult\CodeResult;
use Pmkr\Pmkr\Util\ShellHelper;
use Pmkr\Pmkr\Util\TemplateHelper;
use Sweetchuck\Utils\VersionNumber;

class ExampleCommand extends CommandBase
{

    protected TemplateHelper $templateHelper;

    protected ShellHelper $shellHelper;

    protected function initDependencies()
    {
        if ($this->initialized) {
            return $this;
        }

        parent::initDependencies();
        $container = $this->getContainer();
        $this->templateHelper = $container->get('pmkr.template_helper');
        $this->shellHelper = $container->get('pmkr.shell_helper');

        return $this;
    }

    /**
     * @command example:pmkr
     */
    public function cmdExamplePmkrExecute(
        array $options = [
            'format' => 'code',
        ]
    ): CodeCommandResult {
        $context = [
            'envVars' => $this->shellHelper->collectPhpIniPaths(\PHP_BINARY),
        ];

        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExamplePmkr($context);

        return CodeCommandResult::data($result);
    }

    /**
     * @command example:zplug-plugin-pmkrrc
     */
    public function cmdExampleZplugPluginPmkrRcExecute(
        array $options = [
            'format' => 'code',
        ]
    ): CodeCommandResult {
        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExampleZplugPluginPmkrRc();

        return CodeCommandResult::data($result);
    }

    /**
     * Outputs an example code to add to your `~/.zshrc`.
     *
     * @command example:zplug-entry
     */
    public function cmdExampleZplugEntryExecute(
        $reposDir = '',
        array $options = [
            'format' => 'code',
        ]
    ): CodeCommandResult {
        $context = [];
        if ($reposDir !== '') {
            $context['reposDir'] = $reposDir;
        }

        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExampleZplugEntry($context);

        return CodeCommandResult::data($result);
    }

    /**
     * @command example:rc
     */
    public function cmdExamplePmkrRcExecute(
        array $options = [
            'format' => 'code',
        ]
    ): CodeCommandResult {
        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExamplePmkrRc();

        return CodeCommandResult::data($result);
    }

    /**
     * @command example:instance
     */
    public function cmdExampleInstanceExecute(
        string $coreVersion,
        array $options = [
            'format' => 'code',
        ]
    ): CodeCommandResult {
        $context = [
            'coreVersion' => VersionNumber::createFromString($coreVersion),
        ];

        $result = new CodeResult();
        $result->language = 'yaml';
        $result->code = $this->templateHelper->renderExampleInstance($context);

        return CodeCommandResult::data($result);
    }
}
