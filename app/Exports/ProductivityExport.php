<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use \Maatwebsite\Excel\Sheet;

class ProductivityExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, ShouldAutoSize
{
    private $dataObject;
    private $text_ha;

    public function startCell(): string
    {
        return 'A2';
    }

    public function __construct($dataObject, $text_ha)
    {
        $this->dataObject = $dataObject;
        $this->text_ha = $text_ha;
    }

    public function collection()
    {
        $itens = [];

        // $itens[] = [
        //     'propriedade' => '',
        //     'safra' => '',
        //     'cultura' => '',
        //     'lavoura' => '',
        //     'area' => '',
        //     'date_plant' => '',
        //     'cultivar' => '',
        //     'productivity_per_hectare' => "Produtividade(" . $this->text_ha . ")",
        //     'productivity' => '',
        //     'total_production_per_hectare' => 'Produção',
        //     'total_production' => '',
        // ];

        foreach ($this->dataObject as $object) {
            $itens[] = [
                'propriedade' => $object->property_crop->property ? $object->property_crop->property->name : "--",
                'safra' => $object->property_crop->is_subharvest ? $object->property_crop->subharvest_name : $object->property_crop->harvest->name,
                'cultura' => str_replace(",<br>", ", ", $object->culture_table),
                'lavoura' => $object->property_crop->crop->name,
                'area' => floatval($object->data_seed ? $object->data_seed->area : $object->property_crop->crop->area) . " ",
                'date_plant' => $object->date_plant,
                'cultivar' => str_replace(",<br>", ", ", $object->culture_code_table),
                'productivity_per_hectare' => floatval($object->productivity_per_hectare),
                'productivity' => floatval($object->productivity),
                'total_production_per_hectare' => floatval($object->total_production_per_hectare),
                'total_production' => floatval($object->total_production),
            ];
        }

        return collect($itens);
    }

    public function registerEvents(): array
    {

        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Sheet $sheet */
                $sheet = $event->sheet;

                $sheet->mergeCells('H1:I1');
                $sheet->setCellValue('H1', "Produtividade(" . $this->text_ha . ")");

                $sheet->mergeCells('J1:K1');
                $sheet->setCellValue('J1', "Produção");

                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];

                $cellRange = 'H1:K1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray($styleArray);
            },
        ];
    }

    public function headings(): array
    {
        return [
            "Propriedade",
            "Ano agrícola",
            "Cultura",
            "Lavoura",
            "Área",
            "Plantio",
            "Cultivar",
            "Sc",
            "Kg",
            "Sc",
            "Kg",
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
