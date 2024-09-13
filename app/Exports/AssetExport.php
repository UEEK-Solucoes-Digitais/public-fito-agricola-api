<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AssetExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;

    public function __construct($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function collection()
    {
        $itens = [];

        foreach ($this->dataObject as $asset) {
            $itens[] = [
                'name' => $asset->name,
                'property' => $asset->properties_names,
                'type' => $asset->type ?? '--',
                'value' => $asset->value,
                'observations' => $asset->observations ?? '--',
                'created_at' => Carbon::parse($asset->created_at)->format('d/m/Y H:i:s'),
            ];
        }

        $itens[] = [
            'name' => '',
            'property' => '',
            'city' => 'Total',
            'value' => $this->dataObject->sum('value'),
            'observations' => '',
            'created_at' => '',
        ];

        return collect($itens);
    }

    public function headings(): array
    {
        return ["Nome do bem", "Propriedade", "Tipo", "Valor aproximado", "Observações", "Cadastrado em"];
    }
}
