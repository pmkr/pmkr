<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\OutputFormatter;

use Codeception\Test\Unit;
use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Pmkr\Pmkr\Tests\UnitTester;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TestBase extends Unit
{
    public UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    abstract public function casesWrite(): array;

    /**
     * @param string $expected
     * @param mixed $data
     * @param array{
     *     verbosity?: int,
     *     decorated?: bool,
     * } $outputOptions
     *
     * @dataProvider casesWrite
     */
    public function testWrite(string $expected, $data, array $outputOptions): void
    {
        $formatter = $this->createFormatter($outputOptions);
        $output = $this->createOutput($outputOptions);
        $formatterOptions = new FormatterOptions();
        $formatter->write($output, $data, $formatterOptions);

        $this->tester->assertSame(
            $expected,
            $output->fetch(),
        );
    }

    /**
     * @param array{
     *     verbosity?: int,
     *     decorated?: bool,
     * } $options
     */
    protected function createOutput(array $options): BufferedOutput
    {
        $output = new BufferedOutput();

        if (array_key_exists('verbosity', $options)) {
            $output->setVerbosity($options['verbosity']);
        }

        if (array_key_exists('decorated', $options)) {
            $output->setDecorated($options['decorated']);
        }

        return  $output;
    }

    /**
     * @param array{
     *     verbosity?: int,
     *     decorated?: bool,
     * } $outputOptions
     */
    abstract protected function createFormatter(array $outputOptions): FormatterInterface;
}
