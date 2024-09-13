<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CropsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;

    public function __construct($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function collection()
    {
        $itens = [];

        foreach ($this->dataObject as $crop) {
            $itens[] = [
                'name' => $crop->name,
                'property' => $crop->property ? $crop->property->name : '--',
                'city' => $crop->city,
                'area' => $crop->area,
            ];
        }

        $itens[] = [
            'name' => '',
            'property' => '',
            'city' => 'Total',
            'area' => $this->dataObject->sum('area'),
        ];

        return collect($itens);
    }

    public function headings(): array
    {
        return ["Nome", "Propriedade", "Município", "Área da Lavoura"];
    }
}
