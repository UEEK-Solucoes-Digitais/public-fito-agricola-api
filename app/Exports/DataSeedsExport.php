<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DataSeedsExport implements FromCollection, WithHeadings, ShouldAutoSize
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
            $array = [
                'propriedade' => $object->property_crop->property ? $object->property_crop->property->name : "--",
                'plant' => Carbon::createFromFormat('Y-m-d', $object->date)->format("d/m/Y"),
                'harvest' => $object->property_crop->harvest->name,
                'cultura' => $object->product->name,
                'cultivar' => $object->product_variant,
                'lavoura' => $object->property_crop->crop->name,
            ];

            foreach ($object->data_population as $data_population) {
                $array['plants_per_hectare'] = $data_population->plants_per_hectare;
                $array['quantity_per_ha'] = $data_population->quantity_per_ha;
                $array['emergency_percentage'] = $data_population->emergency_percentage;

                $itens[] = $array;
            }
        }

        return collect($itens);
    }


    public function headings(): array
    {
        return ["Propriedade", "Plantio", "Ano agrícola", "Cultura", "Cultivar", "Lavoura", "Semente Kg/ha", "População/ha", "% de emergência"];
    }
}
