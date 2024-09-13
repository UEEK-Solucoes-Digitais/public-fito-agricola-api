<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DefensiveExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;

    public function __construct($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function collection()
    {
        $itens = [];

        $group = $this->dataObject->data_input->where("type", 2)->groupBy("date")->sortByDesc(function ($item, $key) {
            return $key;
        });

        foreach ($this->dataObject->data_input->where("type", 2)->sortBy('date') as $object) {
            $itens[] = [
                'propriedade' => $this->dataObject->property ? $this->dataObject->property->name : "--",
                'safra' => $this->dataObject->harvest->name,
                'lavoura' => $this->dataObject->crop->name,
                'date' => $object->date ? Carbon::createFromFormat("Y-m-d", $object->date)->format('d/m/Y') : "--",
                'number' => array_search($object->date, array_keys($group->toArray())) + 1,
                'type' => $object->product ? getObjectType($object->product->object_type) : "produto",
                'product' => $object->product ? $object->product->name : "--",
                "dosage" => $object->dosage,
                "total" => $object->dosage * $this->dataObject->crop->area,
            ];
        }

        // total
        $itens[] = [
            'propriedade' => "",
            'safra' => "",
            'lavoura' => "",
            'date' => "",
            'number' => "",
            'type' => "",
            'product' => "Total",
            "dosage" => $this->dataObject->data_input->where("type", 2)->sum("dosage"),
            "total" => $this->dataObject->data_input->where("type", 2)->sum("dosage") * $this->dataObject->crop->area,
        ];

        return collect($itens);
    }

    public function headings(): array
    {
        return ["Propriedade", "Ano agrícola", "Lavoura", "Data", "N•", "Tipo de insumo", "Produto", "Dose", "Total"];
    }
}
