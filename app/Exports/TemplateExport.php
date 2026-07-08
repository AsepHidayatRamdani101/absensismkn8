<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class TemplateExport implements FromArray
{
    public function __construct(
        private readonly array $headings,
        private readonly array $exampleRows = []
    ) {
    }

    public function array(): array
    {
        return array_merge([$this->headings], $this->exampleRows);
    }
}
