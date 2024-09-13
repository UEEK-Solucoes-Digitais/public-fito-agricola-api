<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $dataObject;
    private $tab;

    public function __construct($dataObject, $tab)
    {
        $this->dataObject = $dataObject;
        $this->tab = $tab;
    }

    public function collection()
    {
        $itens = [];

        $productTypes = [
            1 => 'Sementes',
            2 => 'Defensivos',
            3 => 'Fertilizantes',
        ];

        foreach ($this->dataObject as $stock) {
            switch ($this->tab) {
                case 1:
                    $itens[] = [
                        $stock->id,
                        $stock->product->name . ' ' . $stock->product_variant ?? '',
                        $stock->product->type == 4 ? getAlternativeType($stock->alternative_type) : $productTypes[$stock->product->type],
                        $stock->property->name,
                        $stock->stock_quantity_number ?? 0,
                    ];
                    break;
                case 2:
                    $itens[] = [
                        $stock->id,
                        $stock->stock->product->name,
                        $stock->stock->product->type == 4 ? getAlternativeType($stock->stock->alternative_type) : $productTypes[$stock->stock->product->type],
                        $stock->stock->property->name,
                        $stock->quantity,
                        $stock->value * $stock->quantity,
                        $stock->value,
                        $stock->supplier_name,
                        ($stock->nfe_number ? $stock->nfe_number :  '--') . ($stock->nfe_serie ? "- {$stock->nfe_serie}" : ''),
                        $stock->entry_date,
                    ];
                    break;
                case 3:
                    $itens[] = [
                        $stock->id,
                        $stock->stock->product->name,
                        $productTypes[$stock->stock->product->type],
                        $stock->quantity,
                        $stock->stock->property->name,
                        $stock->crop_join->crop->name,
                    ];
                    break;
            }
        }

        // coluna de total de acordo com a tab
        switch ($this->tab) {
            case 1:
                $itens[] = [
                    '',
                    '',
                    '',
                    'Total',
                    $this->dataObject->sum('stock_quantity_number'),
                    '',
                ];
                break;
            case 2:
                $itens[] = [
                    '',
                    '',
                    '',
                    'Total',
                    $this->dataObject->sum('quantity'),
                    $this->dataObject->sum('value') * $this->dataObject->sum('quantity'),
                    $this->dataObject->sum('value'),
                    '',
                    '',
                    '',
                ];
                break;
            case 3:
                $itens[] = [
                    '',
                    '',
                    'Total',
                    $this->dataObject->sum('quantity'),
                    '',
                    '',
                    '',
                ];
                break;
        }

        return collect($itens);
    }

    public function headings(): array
    {
        switch ($this->tab) {
            case 1:
                return [
                    'ID',
                    'Nome do item',
                    'Classe',
                    'Propriedade',
                    'Quantidade em estoque',
                ];
            case 2:
                return [
                    'ID',
                    'Produto',
                    'Classe',
                    'Propriedade',
                    'Quantidade',
                    'Valor',
                    'Valor Unitário',
                    'Fornecedor',
                    'NF-e',
                    'Data NF',
                ];
            case 3:
                return [
                    'ID',
                    'Produto',
                    'Classe',
                    'Quantidade utilizada',
                    'Propriedade',
                    'Lavoura',
                ];
            default:
                return [];
        }
    }
}
