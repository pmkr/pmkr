<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputFormatter;

use Consolidation\OutputFormatters\Formatters\ListFormatter;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo Make it general with a configurable separator.
 * @todo Escape arguments with \escapeshellarg().
 */
class ShellArgumentsFormatter extends ListFormatter
{

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function write(
        OutputInterface $output,
        $data,
        FormatterOptions $options,
    ) {
        $output->write(implode(' ', $data));
    }
}
