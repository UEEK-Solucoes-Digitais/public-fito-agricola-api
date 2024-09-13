<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ApplicationExport implements FromCollection, WithHeadings, ShouldAutoSize
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
                'plant' => $object->date_plant,
                'harvest' => $object->harvest->name,
                'cultura' => str_replace(",<br>", ", ", $object->culture_table),
                'cultivar' => str_replace(",<br>", ", ", $object->culture_code_table),
                'lavoura' => $object->crop->name,
                'application_number' => $object->application_number,
                'application_date_table' => $object->application_date_table,
                'days_between_plant_and_last_application' => $object->days_between_plant_and_last_application,
                'days_between_plant_and_first_application' => $object->days_between_plant_and_first_application,
                'application_table' => $object->application_table,
                'emergency_table' => $object->emergency_table,
                'plant_table' => $object->plant_table,
                'stage_table' => $object->stage_table,
            ];
        }

        return collect($itens);
    }


    public function headings(): array
    {
        return ["Propriedade", "Plantio", "Ano agrícola", "Cultura", "Cultivar", "Lavoura", "N•", "Data", "DEPUA - Fungicida", "DEPPA - Fungicida", "DAA - Fungicida", "DAE", "DAP", "Estádio"];
    }
}
