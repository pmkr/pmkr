<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\ProcessResultParser;

use Codeception\Test\Unit;
use phpDocumentor\Reflection\ProjectFactory;
use Pmkr\Pmkr\ProcessResultParser\ParserInterface;
use Pmkr\Pmkr\Tests\UnitTester;
use Twig\Parser;

abstract class TestBase extends Unit
{

    protected UnitTester $tester;

    abstract protected function createParser(): ParserInterface;

    /**
     * @return array<string, mixed>
     */
    abstract public function casesParser(): array;

    /**
     * @param array{
     *     exitCode: int,
     *     assets: array<string, mixed>,
     * } $expected
     *
     * @dataProvider casesParser
     */
    public function testParse(
        array $expected,
        int $exitCode,
        string $stdOutput,
        string $stdError
    ): void {
        $parser = $this->createParser();

        $this->tester->assertSame(
            $expected,
            $parser->parse($exitCode, $stdOutput, $stdError),
        );
    }
}
