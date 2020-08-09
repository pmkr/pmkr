<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputConverter;

use Pmkr\Pmkr\Model\Library;
use Pmkr\Pmkr\Utils;
use Symfony\Component\Filesystem\Filesystem;

class LibraryConverter
{

    /**
     * @param iterable|\Pmkr\Pmkr\Model\Instance[] $libraries
     * @param bool $isHuman
     *
     * @return array
     */
    public function toFlatRows(iterable $libraries, bool $isHuman): array
    {
        $rows = [];
        foreach ($libraries as $library) {
            $rows[$library->key] = $this->toFlatRow($library, $isHuman);
        }

        return $rows;
    }

    public function toFlatRow(Library $library, bool $isHuman): array
    {
        return [
            'key' => $library->key,
            'name' => $library->name,
        ];
    }
}
