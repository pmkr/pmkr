<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\VariationPickResult;

use Pmkr\Pmkr\Model\Instance;

class VariationPickResult
{
    public ?float $weight = null;

    public ?Instance $instance = null;

    public ?string $phpRc = null;

    /**
     * @var null|array<string>
     */
    public ?array $phpIniScanDir = null;

    public ?string $binary = null;

    public bool $export = true;

    /**
     * @param array{
     *     weight?: int|float,
     *     instance?: ?\Pmkr\Pmkr\Model\Instance,
     *     phpRc?: ?string,
     *     phpIniScanDir?: ?array<string>,
     *     binary?: ?string,
     *     export?: bool,
     * } $values
     *
     * @return static
     */
    public static function __set_state($values)
    {
        $self = new static();
        foreach ($values as $key => $value) {
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
