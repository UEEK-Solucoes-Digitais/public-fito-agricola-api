<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PestsExport implements FromCollection, WithHeadings, ShouldAutoSize
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
                'propriedade' => $object->property_crop->property ? $object->property_crop->property->name : "--",
                'safra' => $object->property_crop->harvest->name,
                'cultura' => str_replace(",<br>", ", ", $object->property_crop->culture_table),
                'cultivar' => str_replace(",<br>", ", ", $object->property_crop->culture_code_table),
                'lavoura' => $object->property_crop->crop->name,
                'dap' => $object->open_date ? Carbon::createFromFormat("Y-m-d", $object->open_date) : "--",
                'pest' => $object->pest->name,
                'dae' => $this->getRisk($object->risk),
                'daa' => $object->incidency,
                'meter' => number_format($object->quantity_per_meter, 2, ",", "."),
                'quantity_per_square_meter' => number_format($object->quantity_per_square_meter, 2, ",", "."),
                'estadio' => $object->pest->observations,
                'stage' => $object->property_crop->stage_table,
                'admin' => $object->admin->name,
            ];
        }

        return collect($itens);
    }

    public function getRisk($risk)
    {
        switch ($risk) {
            case 1:
                return "Sem risco";
                break;
            case 2:
                return "Atenção";
                break;
            case 3:
                return "Urgência";
                break;
        }
    }

    public function headings(): array
    {
        return ["Propriedade", "Ano agrícola", "Cultura", "Cultivar", "Lavoura", "Data", "Praga", "Nível de risco", "Incidência", "Metro", "m²", "Observações", "Estádio", "Responsável"];
    }
}
