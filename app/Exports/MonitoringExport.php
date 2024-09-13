<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MonitoringExport implements FromCollection, WithHeadings, ShouldAutoSize
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
                'propriedade' => $object->property ? $object->property->name : "--",
                'lavoura' => $object->crop->name,
                'cultura' => str_replace(",<br>", ", ", $object->culture_table),
                'cultivar' => str_replace(",<br>", ", ", $object->culture_code_table),
                'harvest' => $object->harvest->name,
            ];

            foreach ($object->management_data as $key => $management_data) {
                $management_data = (object) $management_data;
                $array['date'] = str_replace("-", "/", $key);
                $array['stage'] = "";
                foreach ($management_data->stages as $stage) {
                    $stage_text = "";

                    if ($stage->vegetative_age_value) {
                        $stage_text .= "V" . $stage->vegetative_age_value;
                    }

                    if ($stage->vegetative_age_value && $stage->reproductive_age_value) {
                        $stage_text .= " - ";
                    }

                    if ($stage->reproductive_age_value) {
                        $stage_text .= "R" . $stage->reproductive_age_value;
                    }

                    $array['stage'] .= $stage_text . ", ";
                }
                $array['stage'] = substr($array['stage'], 0, -2);

                $array['diseases'] = "";
                foreach ($management_data->diseases as $disease) {
                    $array['diseases'] .= $disease->disease->name . " - " . number_format($disease->incidency, 2, ",", ".") . "%, ";
                }
                $array['diseases'] = substr($array['diseases'], 0, -2);

                $array['pests'] = "";
                foreach ($management_data->pests as $pest) {
                    $array['pests'] .= $pest->pest->name . " - " . number_format($pest->incidency, 2, ",", ".");

                    if ($pest->quantity_per_meter) {
                        $array['pests'] .= " - " . number_format($pest->quantity_per_meter, 2, ",", ".") . "/m";
                    }

                    if ($pest->quantity_per_square_meter) {

                        $array['pests'] .= " - " . number_format($pest->quantity_per_square_meter, 2, ",", ".") . "/m²";
                    }

                    $array['pests'] .= ", ";
                }
                $array['pests'] = substr($array['pests'], 0, -2);

                $array['weeds'] = "";
                foreach ($management_data->weeds as $weed) {
                    $array['weeds'] .= $weed->weed->name . ", ";
                }
                $array['weeds'] = substr($array['weeds'], 0, -2);

                $array['observations'] = isset($management_data->observations[0]) ? $management_data->observations[0]->observations : "";

                $itens[] = $array;
            }
        }

        return collect($itens);
    }

    public function headings(): array
    {
        return ["Propriedade", "Lavoura", "Cultura", "Cultivar", "Ano Agrícola", "Data", "Estádios", "Doenças", "Pragas", "Daninhas", "Observações"];
    }
}
