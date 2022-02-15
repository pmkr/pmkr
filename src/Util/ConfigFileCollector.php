<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Application;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Finder\Finder;

class ConfigFileCollector
{

    protected Utils $utils;

    protected Finder $finder;

    /**
     * @var null|\IteratorAggregate<string, \Symfony\Component\Finder\SplFileInfo>
     */
    protected ?iterable $configFiles = null;

    public function __construct(
        Utils $utils,
        Finder $finder
    ) {
        $this->utils = $utils;
        $this->finder = $finder;
    }

    /**
     * @return \IteratorAggregate<string, \Symfony\Component\Finder\SplFileInfo>
     */
    public function collect(): iterable
    {
        if ($this->configFiles === null) {
            $appName = Application::NAME;
            $pmkrHome = $this->utils->getPmkrHome();
            $this->configFiles = $this
                ->finder
                ->in($pmkrHome)
                ->files()
                ->name("$appName.*.yml")
                ->sortByName(true);
        }

        return $this->configFiles;
    }

    /**
     * @return array<string>
     */
    public function collectAsChoices(): array
    {
        $choices = [];
        foreach ($this->collect() as $file) {
            $choices[] = $file->getPathname();
        }

        return $choices;
    }
}
