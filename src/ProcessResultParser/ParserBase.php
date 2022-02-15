<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\ProcessResultParser;

abstract class ParserBase implements ParserInterface
{

    /**
     * @var array<string, string>
     */
    protected array $assetNameMapping = [];

    public function getAssetNameMapping(): array
    {
        return $this->assetNameMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setAssetNameMapping(array $value)
    {
        $this->assetNameMapping = $value;

        return $this;
    }

    protected function getExternalAssetName(string $internalAssetName): string
    {
        return $this->assetNameMapping[$internalAssetName] ?? $internalAssetName;
    }
}
