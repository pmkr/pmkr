<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\VariationPickResult;

use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\Transformations\StringTransformationInterface;
use Pmkr\Pmkr\Model\Instance;

class VariationPickResult implements
    \JsonSerializable,
    \Stringable,
    StringTransformationInterface
{
    public ?string $key = null;

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
     *     key?: ?string,
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
                case 'key':
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

    public function __toString()
    {
        return (string) $this->key;
    }

    /**
     * @return array{
     *     key?: ?string,
     *     weight?: null|int|float,
     *     instanceKey?: ?string,
     *     phpRc?: ?string,
     *     phpIniScanDir?: ?array<string>,
     *     binary?: ?string,
     *     export?: bool,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'weight' => $this->weight,
            'instanceKey' => $this->instance ? $this->instance->key : null,
            'phpRc' => $this->phpRc,
            'phpIniScanDir' => $this->phpIniScanDir,
            'binary' => $this->binary,
            'export' => $this->export,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function simplifyToString(FormatterOptions $options)
    {
        return (string) $this;
    }

    public function implodePhpIniScanDir(): ?string
    {
        // @todo What if a directory name contains a DIRECTORY_SEPARATOR character?
        return $this->phpIniScanDir === null ?
            null
            : implode(\DIRECTORY_SEPARATOR, $this->phpIniScanDir);
    }
}
