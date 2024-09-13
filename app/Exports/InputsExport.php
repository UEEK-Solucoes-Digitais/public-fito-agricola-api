<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InputsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;
    private $visualization_type;

    public function __construct($dataObject, $visualization_type = 1)
    {
        $this->dataObject = $dataObject;
        $this->visualization_type = $visualization_type;
    }

    public function collection()
    {
        $itens = [];

        foreach ($this->dataObject as $object) {
            $array['propriedade'] = $this->visualization_type == 3 ? $object->name : $object->property->name;
            if ($this->visualization_type != 3) {
                $array['lavoura'] = $object->crop->name;
            }

            $array['cultura'] = str_replace(",<br>", ", ", $object->culture_table);
            $array['harvest'] = $this->visualization_type == 3 ? $object->harvest : $object->harvest->name;

            foreach ($object->merged_data_input as $merged_data_input) {
                if ($this->visualization_type == 1) {
                    $array['date'] = Carbon::createFromFormat('Y-m-d', $merged_data_input['date'])->format("d/m/Y");
                }
                $array['type'] = $merged_data_input['product'] ? (isset($merged_data_input['type']) ? ($merged_data_input['type'] == 1 ? "Fertilizantes" : getObjectType($merged_data_input['product']['object_type'])) : "Sementes") : "--";
                $array['name'] = $merged_data_input['product'] ? $merged_data_input['product']['name'] : "--";
                $array['sum'] = isset($merged_data_input['type']) ? $merged_data_input['dosage'] : $merged_data_input['kilogram_per_ha'];
                $array['sum_total'] = $this->visualization_type != 3 ? ((isset($merged_data_input['type']) ? $merged_data_input['dosage'] : $merged_data_input['kilogram_per_ha']) * $object->crop->area) : $merged_data_input['total_dosage'];

                $itens[] = $array;
            }
        }


        return collect($itens);
    }



    public function headings(): array
    {
        switch ($this->visualization_type) {
            case 1:
                return ["Propriedade", "Lavoura", "Cultura", "Ano agrícola", "Data", "Classe", "Produto", "Dose/ha", "Quantidade"];
            case 2:
                return ["Propriedade", "Lavoura", "Cultura", "Ano agrícola",  "Classe", "Produto", "Dose/ha", "Quantidade"];
            case 3:
                return ["Propriedade", "Cultura", "Ano agrícola",  "Classe", "Produto", "Dose/ha", "Quantidade"];
        }
    }
}
