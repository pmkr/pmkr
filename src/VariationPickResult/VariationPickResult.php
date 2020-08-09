<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\VariationPickResult;

use Pmkr\Pmkr\Model\Instance;

class VariationPickResult
{
    public ?float $weight = null;

    public ?Instance $instance = null;

    public ?string $phpRc = null;

    public ?array $phpIniScanDir = null;

    public ?string $binary = null;

    public bool $export = true;

    public static function __set_state($an_array)
    {
        $self = new static();
        foreach ($an_array as $key => $value) {
            switch ($key) {
                case 'weight':
                case 'instance':
                case 'phpRc':
                case 'phpIniScanDir':
                case 'binary':
                case 'export':
                    $self->$key = $value;
                    break;
            }
        }

        return $self;
    }

    public function implodePhpIniScanDir(): ?string
    {
        // @todo What if a directory name contains a DIRECTORY_SEPARATOR character?
        return $this->phpIniScanDir === null ?
            null
            : implode(\DIRECTORY_SEPARATOR, $this->phpIniScanDir);
    }
}
