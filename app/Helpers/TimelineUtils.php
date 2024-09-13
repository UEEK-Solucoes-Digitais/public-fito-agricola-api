<?php

use App\Models\Crop;
use App\Models\Harvest;
use App\Models\LogSystem;
use App\Models\Property;
use App\Models\PropertyCropJoin;
use Illuminate\Support\Facades\DB;

class TimelineUtils
{
   
    public function filterLogs($timelineGroupedByDate, $filters)
    {
        foreach ($filters as $name => $value) {
            if (empty($value)) {
                continue;
            }

            $timelineGroupedByDate = $timelineGroupedByDate->map(function ($logs) use ($name, $value) {
                return $logs->filter(function ($log) use ($name, $value) {
                    switch ($name) {
                        case 'property':
                            return $log->property_id == $value;
                        case 'harvest':
                            return $log->harvest_id == $value;
                        case 'crop':
                            return $log->crop_id == $value;
                        default:
                            return true;
                    }

                });
            });

            $timelineGroupedByDate = $timelineGroupedByDate->filter(function ($logs) {
                return $logs->isNotEmpty();
            });
        }

        return $timelineGroupedByDate;
    }

    public function addFormattedLogInformations($logsGroupedByDate)
    {
        $queryMap = [];

        $propertyIds = [];
        $cropJoinIds = [];
        $inputIds = [];

        $cropIds = [];
        // $harvestIds = [];

        foreach ($logsGroupedByDate as $logs) {
            foreach($logs as $log) {
                $query = DB::table($log->table_name)->find($log->object_id);
                $queryMap[$log->object_id] = $query;

                if ($query && isset($query->property_id)) {
                    $propertyIds[] = $query->property_id;
                }

                if ($query && (isset($query->properties_crops_id) || $log->table_name === "properties_crops_join")) {
                    $cropJoinIds[] = isset($query->properties_crops_id) ? $query->properties_crops_id : $query->id;
                }

                if ($query && $log->table_name === 'properties_management_data_inputs') {
                    $inputIds[] = $log->object_id;
                }

                // if ($query && isset($query->harvest_id)) {
                //     $harvestIds[] = $log->harvest_id;
                // }

                if ($query && isset($query->crop_id) || $log->table_name === "crops") {
                    $cropIds[] = $log->crop_id ?? $log->object_id;
                }
            }
        }

        $properties = Property::whereIn('id', $propertyIds)->get()->keyBy('id');
        $cropJoins = PropertyCropJoin::whereIn('id', $cropJoinIds)->with(['property', 'crop', 'harvest'])->get()->keyBy('id');
        $inputs = DB::table('properties_management_data_inputs')->whereIn('id', $inputIds)->get()->keyBy('id');
        $crops = Crop::whereIn('id', $cropIds)->get()->keyBy('id');
        // $harvests = Harvest::whereIn('id', $cropJoins->pluck('harvest_id'))->get()->keyBy('id');
        
        return $logsGroupedByDate->map(function($logs) use ($properties, $cropJoins, $inputs, $crops, $queryMap) {
            return $logs->map(function($log) use ($properties, $cropJoins, $inputs, $crops, $queryMap) {

                $query = $queryMap[$log->object_id];

                if ($query && isset($query->crop_id) || $log->table_name === "crops") {
                    $crop = $crops[$query->crop_id ?? $query->id] ?? null;
                    if ($crop) {
                        $log->crop_name = $crop->name;
                        $log->crop_id = $crop->id;
                    }
                }

                // if ($query && isset($query->harvest_id)) {
                //     $harvest = $harvests[$query->harvest_id] ?? null;
                //     if ($harvest) {
                //         $log->harvest_name = $harvest->name;
                //         $log->harvest_id = $harvest->id;
                //     }
                // }

                if ($query && isset($query->property_id)) {
                    $property = $properties[$query->property_id] ?? null;

                    if ($property) {
                        $log->property_name = $property->name;
                        $log->property_id = $property->id;
                    }
                }

                if ($query && isset($query->property_id)) {
                    $property = $properties[$query->property_id] ?? null;

                    if ($property) {
                        $log->property_name = $property->name;
                        $log->property_id = $property->id;
                    }
                }

                if ($query && (isset($query->properties_crops_id) || $log->table_name === "properties_crops_join")) {

                    $id = isset($query->properties_crops_id) ? $query->properties_crops_id : $query->id;

                    $join = $cropJoins[$id] ?? null;

                    if ($join) {
                        if ($join->property) {
                            $log->property_name = $join->property->name;
                            $log->property_id = $join->property->id;
                        }

                        if ($join->harvest) {
                            $log->harvest_name = $join->harvest->name;
                            $log->harvest_id = $join->harvest->id;
                        }

                        if ($join->crop) {
                            $log->crop_name = $join->crop->name;
                            $log->crop_id = $join->crop->id;
                        }

                        if ($log->table_name === "properties_crops_join") {
                            $log->crop_to_harvest = true;
                        }
                    }
                }

                if ($query && $log->table_name === 'properties_management_data_inputs') {
                    $input = $inputs[$log->object_id] ?? null;
                    if ($input) {
                        $log->defensive = $input->type === 2;
                    }
                }

                if ($log->operation === 2) {
                    $parsedTo = json_decode($log->to);
                    if (isset($parsedTo->status) && $parsedTo->status === 0) {
                        $log->operation = 3;
                    }
                }

                $log = $this->parseLogName($log);

                return $log;
            });
        });
    }

    private function parseLogName($log)
    {
        switch($log->table_name){
            case "crops":
                $log->formatted_table = "Lavoura";
                $log->specific_name = Crop::where('id', $log->object_id)->first()->name;
                break;
            case "properties":
                $log->formatted_table = "Propriedade";
                break;
            case "properties_crops_diseases";
                $log->formatted_table = "Doença";
                break;
            case "properties_crops_join";
                $log->formatted_table = "Lavoura (Safra)";
                break;
            case "properties_crops_observations";
                $log->formatted_table = "Observação";
                break;
            case "properties_crops_pests";
                $log->formatted_table = "Praga";
                break;
            case "properties_crops_rain_gauge";
                $log->formatted_table = "Lançamento de chuva";
                break;
            case "properties_crops_stage";
                $log->formatted_table = "Estágio";
                break;
            case "properties_crops_weeds";
                $log->formatted = "Daninha";
                break;
            case "properties_management_data_harvest";
                $log->formatted_table = "Colheita";
                break;
            case "properties_management_data_inputs";
                $log->formatted_table = $log->defensive ? 'Defensivo' : 'Fertilizante';
                break;
            case "properties_management_data_population";
                $log->formatted_table = "População";
                break;
            case "properties_management_data_seeds";
                $log->formatted_table = "Sementes";
                break;
            default:
                $log->formatted_table = "NÃO PREVISTO";
        }

        return $log;
    }
}

?>