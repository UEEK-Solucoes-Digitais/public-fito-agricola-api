<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RainGaugesExport implements FromCollection, WithHeadings, ShouldAutoSize
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
                'safra' => $object->harvest->name,
                'lavoura' => $object->crop->name,
                'total_volume' => floatval($object->rain_gauge_infos['total_volume']),
                'avg_volume' => floatval($object->rain_gauge_infos['avg_volume']),
                'rain_interval' => floatval($object->rain_gauge_infos['rain_interval']) . " dias",
                'days_with_rain' => floatval($object->rain_gauge_infos['days_with_rain']) . " dias",
                'days_without_rain' => floatval($object->rain_gauge_infos['days_without_rain']) . " dias",
            ];
        }

        return collect($itens);
    }


    public function headings(): array
    {
        return [
            'Propriedade',
            'Cultura',
            'Cultivar',
            'Ano agrícola',
            'Lavoura',
            'Total',
            'Média do volume',
            'Intervalo sem chuva',
            'Dias com chuva',
            'Dias sem chuva'
        ];
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
