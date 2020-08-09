<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\ProcessResultParser\ParserInterface;

class TerminalColorSchemeDetector
{

    /**
     * @todo ConfigAwareInterface.
     */
    protected ConfigInterface $config;

    protected ProcessFactory $processFactory;

    protected ParserInterface $terminalColorParser;

    public function __construct(
        ConfigInterface $config,
        ProcessFactory $processFactory,
        ParserInterface $terminalColorParser
    ) {
        $this->config = $config;
        $this->processFactory = $processFactory;
        $this->terminalColorParser = $terminalColorParser;
    }

    public function getTheme(): ?string
    {
        $fgBg = $this->getFgBg();
        if ($fgBg) {
            return $fgBg['fg'] === 15 || $fgBg['bg'] === 0 ?
                'dark'
                : 'light';
        }

        $bgRgb = $this->getBgRgb();
        if ($bgRgb) {
             return array_sum($bgRgb) / count($bgRgb) < (256 * 256 / 2) ?
                 'dark'
                 : 'light';
        }

        return null;
    }

    protected function getFgBg(): ?array
    {
        $value = $this->config->get('env.COLORFGBG') ?? '';
        $matches = [];
        preg_match('/^(\d+);(\d+)$/', $value, $matches);
        if (!$matches) {
            return null;
        }

        return [
            'fg' => (int) $matches[1],
            'bg' => (int) $matches[2],
        ];
    }

    protected function getBgRgb(): ?array
    {
        $command = ['printf', '\033]11;?\007'];
        $process = $this->processFactory->createInstance($command);
        $process->run();
        $result = $this->terminalColorParser->parse(
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
        );

        if ($result['exitCode'] !== 0) {
            return null;
        }

        return $result['assets']['rgb_10'] ?? null;
    }
}
