<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\ProcessResultParser;

interface ParserInterface
{

    /**
     * @return array<string, string>
     */
    public function getAssetNameMapping(): array;

    /**
     * @param array<string, string> $value
     */
    public function setAssetNameMapping(array $value): static;

    /**
     * @return array{
     *     exitCode: int,
     *     assets: array<string, mixed>,
     * }
     */
    public function parse(int $exitCode, string $stdOutput, string $stdError): array;
}
