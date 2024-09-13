<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RainGaugesDetailedExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;
    private $rain_gauges_infos;

    public function __construct($dataObject, $rain_gauges_infos)
    {
        $this->dataObject = $dataObject;
        $this->rain_gauges_infos = $rain_gauges_infos;
    }

    public function collection()
    {
        $itens = [];

        foreach ($this->dataObject as $object) {
            $itens[] = [
                'propriedade' => $object->property_crop->property->name,
                'lavoura' => $object->property_crop->crop->name,
                'safra' => $object->property_crop->harvest->name,
                'data' => $object->date,
                'volume' => number_format($object->volume, 2, ',', '.') . "mm",
            ];
        }

        $itens[] = [
            'data' => '',
            'volume' => '',
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        $itens[] = [
            'data' => 'Volume total',
            'volume' => $this->rain_gauges_infos['total_volume'] . "mm",
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        $itens[] = [
            'data' => 'Média de volume',
            'volume' => $this->rain_gauges_infos['avg_volume'] . "mm",
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        $itens[] = [
            'data' => 'Dias com chuva',
            'volume' => $this->rain_gauges_infos['days_with_rain'] . ($this->rain_gauges_infos['days_with_rain'] > 1 ? ' dias' : ' dia'),
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        $itens[] = [
            'data' => 'Maior intervalo sem chuva',
            'volume' => $this->rain_gauges_infos['rain_interval']  . ($this->rain_gauges_infos['rain_interval'] > 1 ? ' dias' : ' dia'),
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        $itens[] = [
            'data' => 'Dias sem chuva',
            'volume' => $this->rain_gauges_infos['days_without_rain']  . ($this->rain_gauges_infos['days_with_rain'] > 1 ? ' dias' : ' dia'),
            'propriedade' => '',
            'lavoura' => '',
            'safra' => '',
        ];

        return collect($itens);
    }


    public function headings(): array
    {
        return [
            'Propriedade',
            'Lavoura',
            'Ano Agrícola',
            'Data',
            'Volume',
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
