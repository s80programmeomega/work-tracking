<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Classeur Excel multi-feuilles pour l'export des résultats de recherche.
 * Chaque type (projets, taches, documents…) est une feuille distincte.
 */
class MultiSheetSearchExport implements WithMultipleSheets
{
    /** @param  SearchExport[]  $sheets */
    public function __construct(private readonly array $sheets) {}

    /** @return SearchExport[] */
    public function sheets(): array
    {
        return $this->sheets;
    }
}
