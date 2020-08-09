<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\Utils;
use Twig\Environment as TwigEnvironment;

class TemplateHelper implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    protected TwigEnvironment $twig;

    protected Utils $utils;

    public function __construct(
        ConfigInterface $config,
        Utils $utils,
        TwigEnvironment $twig
    ) {
        $this->setConfig($config);
        $this->twig = $twig;
        $this->utils = $utils;
    }

    public function renderExamplePmkr(array $context = []): string
    {
        $shell = basename($this->getConfig()->get('env.SHELL') ?: '/bin/zsh');
        $context += [
            'shell' => $shell,
            'envVars' => [],
            'phpBinary' => \PHP_BINARY,
            'pmkrRoot' => $this->utils->getPmkrRoot(),
        ];
        $context['envVars'] = $this->utils->envVarsToExpressions($context['envVars']);

        return $this->twig->render('example/pmkr.zsh.twig', $context);
    }

    public function renderExampleZplugEntry(array $context = []): string
    {
        $config = $this->getConfig();
        $reposDir = (string) $config->get('env.ZPLUG_REPOS');
        if ($reposDir === '') {
            $home = (string) $config->get('env.HOME');
            $reposDir = "$home/.zplug/repos";
        }

        $context += [
            'reposDir' => $reposDir,
            'name' => 'pmkrrc',
        ];

        return $this->render('zplug/entry.zsh.twig', $context);
    }

    public function renderExampleZplugPluginPmkrRc(array $context = []): string
    {
        return $this->render('zplug/plugin/pmkrrc/pmkrrc.zsh.twig', $context);
    }

    public function renderExamplePmkrRc(array $context = []): string
    {
        $shell = basename($this->getConfig()->get('env.SHELL') ?: '/bin/zsh');
        $context += [
            'shell' => $shell,
        ];

        return $this->render('example/pmkrrc.zsh.twig', $context);
    }

    /**
     * @param array $context
     *   Required keys:
     *   - coreVersion
     *
     * @return string
     */
    public function renderExampleInstance(array $context = []): string
    {
        return $this->render('example/instance.yml.twig', $context);
    }

    public function render(string $name, array $context): string
    {
        $context += ['env' => []];
        $context['env'] += $this->getConfig()->get('env');

        return $this->twig->render($name, $context);
    }
}
