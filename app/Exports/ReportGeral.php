<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportGeral implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;

    public function __construct($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function collection()
    {
        $itens = [];

        foreach ($this->dataObject as $object) {
            $itens[] = [
                'propriedade' => $object->property ? $object->property->name : "--",
                'cultura' => str_replace(",<br>", ", ", $object->culture_table),
                'cultivar' => str_replace(",<br>", ", ", $object->culture_code_table),
                'area' => $object->crop->area,
                'lavoura' => $object->crop->name,
                'dap' => $object->plant_table,
                'dae' => $object->emergency_table,
                'daa' => $object->application_table,
                'estadio' => $object->stage_table,
            ];
        }

        return collect($itens);
    }


    public function headings(): array
    {
        return ["Propriedade", "Cultura", "Cultivar", "Área", "Lavoura", "DAP", "DAE", "DAA", "Estádio"];
    }

    // public function map($user): array
    // {
    //     return [
    //         $user->id,
    //         $user->name,
    //         $user->email,
    //         // Outros campos conforme necessário
    //     ];
    // }
}
