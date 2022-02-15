<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\OutputConverter;

use Pmkr\Pmkr\Model\Library;

class LibraryConverter
{

    /**
     * @param iterable<string, \Pmkr\Pmkr\Model\Library> $libraries
     * @param bool $isHuman
     *
     * @return array<
     *     string,
     *     array{
     *         key: string,
     *         name: string,
     *     }
     * >
     */
    public function toFlatRows(iterable $libraries, bool $isHuman): array
    {
        $rows = [];
        foreach ($libraries as $library) {
            $rows[$library->key] = $this->toFlatRow($library, $isHuman);
        }

        return $rows;
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     * }
     */
    public function toFlatRow(Library $library, bool $isHuman): array
    {
        return [
            'key' => $library->key,
            'name' => $library->name,
        ];
    }
}
