<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Minimal importable that returns the raw rows as a 2D array.
 * Used because Excel::toArray(null, ...) is not a valid API in
 * maatwebsite/excel 3.1 — the first argument must implement an
 * Importable concern.
 */
class RawSheetImport implements ToArray
{
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
