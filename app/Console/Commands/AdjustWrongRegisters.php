<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\LogSystem;
use App\Models\PropertyCropJoin;
use App\Models\StockExit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdjustWrongRegisters extends Command
{
    protected $signature = 'app:adjust-wrong-registers';

    protected $description = 'Ajustando lançamentos que foram lançados incorretos pelo app desatualizado 09/07/2024';

    public function handle()
    {
        $log_system = LogSystem::where('operation', 1)
            ->whereIn('table_name', [
                'properties_management_data_seeds', 'stock_exits', 'stocks', 'properties_management_data_inputs', 'stock_incomings', 'properties_management_data_population'
            ])
            ->whereHas('admin', function ($q) {
                $q->where('access_level', '!=', 1);
            })
            ->where('created_at', '>=', '2024-07-09 00:00:00')
            ->get();

        $wrong_registers = [];

        foreach ($log_system as $log) {
            $admin = $log->admin;
            $all_properties = [];


            if ($admin) {
                $all_properties = $admin->all_properties();
                $crops_join = PropertyCropJoin::whereIn('property_id', $all_properties->pluck('id')->toArray())->get();
            }
            checkSection($admin->id);

            if (in_array($log->table_name, ['properties_management_data_seeds', 'properties_management_data_inputs', 'properties_management_data_population'])) {
                $type = "";

                switch ($log->table_name) {
                    case 'properties_management_data_seeds':
                        $item = $log->data_seed;
                        $type = "seed";
                        break;
                    case 'properties_management_data_inputs':
                        $item = $log->data_input;
                        $type = $item->type == 1 ? "fertilizer" : "defensive";
                        break;
                    case 'properties_management_data_population':
                        $item = $log->data_population;
                        break;
                }

                if ($item && $item->property_crop) {

                    if (!in_array($item->property_crop->id, $crops_join->pluck('id')->toArray())) {
                        $correct_join = PropertyCropJoin::where('crop_id', $item->property_crop->id)->where('status', 1)->first();


                        if ($correct_join) {
                            $crop = Crop::find($item->property_crop->crop_id);
                            echo "atualizando log {$log->id}\n";
                            echo "produto: {$item->product->name}\n";
                            echo "lavoura antiga: {$crop->name} \n";
                            echo "lavoura correta: {$correct_join->property->name} - {$correct_join->crop->name} - {$correct_join->harvest->name} \n";
                            echo "administrador: {$admin->name} \n\n";

                            if ($type != "") {
                                $exit = StockExit::where('properties_crops_id', $item->property_crop->id)->where('type', $type)->where('object_id', $item->id)->orderBy('id', 'desc')->first();

                                if ($exit) {
                                    $exit->properties_crops_id = $correct_join->id;
                                    $exit->save();

                                    $stock = $exit->stock;

                                    if ($stock) {
                                        $stock->property_id = $correct_join->property->id;
                                        $stock->save();
                                    }
                                }
                            }

                            $item->properties_crops_id = $correct_join->id;
                            $item->save();
                        }
                    }
                } else {
                    echo "Item não encontrado: {$log->table_name}\n";
                }
            }
        }
        var_dump($wrong_registers);
    }
}
