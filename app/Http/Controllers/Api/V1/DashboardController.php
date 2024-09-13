<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Crop;
use App\Models\Admin;
use App\Models\Harvest;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\PropertyCropPest;
use App\Models\PropertyCropWeed;
use App\Models\PropertyCropStage;
use App\Models\PropertyCropDisease;
use App\Http\Controllers\Controller;
use App\Exceptions\OperationException;
use App\Models\LogSystem;
use App\Models\PropertyCropJoin;
use App\Models\PropertyCropObservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Time;
use TimelineUtils;

class DashboardController extends Controller
{
    public function getItens($admin_id, Request $request)
    {
        try {

            list($properties, $total) = Property::readProperties($admin_id, null, null, ['id', 'name', 'city', 'status', 'admin_id']);
            $admins = Admin::select('id', 'name')->where('status', '!=', 0)->orderBy("name", "ASC");

            // se o usuário que está lendo não for admin, somente os itens cadastrados por ele serão lidos
            $admin = Admin::find($admin_id);
            if ($admin->access_level != 1) {
                $admins->whereHas("properties_many", function ($q) use ($properties) {
                    $q->whereIn("properties.id", $properties->pluck("id"));
                });
            }

            $admins = $admins->get();

            $crops = Crop::select('id', 'name', 'area', 'property_id')->where("status", 1)->whereIn('property_id', $properties->pluck("id"))->orderBy("name", "ASC")->get();

            // if ($request->get("with_draw_area")) {
            //     $crops->each(function ($crop) {
            //         $crop->color = $crop->crops_join->first() ? ($crop->crops_join->first()->data_seed->first() && $crop->crops_join->first()->data_seed->first()->product ? $crop->crops_join->first()->data_seed->first()->product->color : null) : null;
            //     });
            // }

            $harvests = Harvest::select('id', 'name', 'is_last_harvest')->where("status", 1)->orderBy("name", "ASC")->get();

            $total_area = 0;
            $properties->each(function ($property) use (&$total_area) {
                $total_area += floatval(isString($property->total_area));
            });

            return response()->json([
                'status' => 200,
                'properties' => $properties,
                'crops' => $crops,
                'harvests' => $harvests,
                'admins' => $admins,
                'last_harvest_id' => $harvests->where('is_last_harvest', 1)->first() ? $harvests->where('is_last_harvest', 1)->first()->id : null,
                'total_area' => $total_area,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCrops($admin_id, Request $request)
    {
        try {
            $crops = Crop::with(['crops_join' => function ($q) {
                $q->whereHas('data_seed', function ($q) {
                    $q->where('status', 1)->whereHas('product', function ($q) {
                        $q->where('status', 1)->where('color', '!=', null);
                    });
                })->with('data_seed.product');
            }])
                ->select('id', 'name', 'area', 'property_id', 'draw_area')
                ->where("status", 1)
                ->whereHas('crops_join', function ($q) {
                    $q->where("status", 1);
                })
                ->orderBy("name", "ASC");

            // Restrições de acesso para não-admin
            if (optional(Admin::find($admin_id))->access_level != 1) {
                $crops->whereHas('property', function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            }

            $crops = $crops->get();

            $admin = Admin::find($admin_id);

            $crops->each(function ($crop) use ($request, $admin) {

                if ($admin->actual_harvest_id) {
                    $firstCropsJoin = $crop->crops_join->where("harvest_id", $admin->actual_harvest_id)->first();
                } else {
                    $actual_harvest = Harvest::where('is_last_harvest', 1)->where('status', 1)->orderBy("id", "DESC")->first();
                    $firstCropsJoin = $crop->crops_join->where("harvest_id", $actual_harvest->id)->first();
                }

                $crop->color = $firstCropsJoin ? $firstCropsJoin->data_seed->sortByDesc('area')->first()->product->color : null;

                unset($crop->crops_join);
            });

            return response()->json([
                'status' => 200,
                'crops' => $crops,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function getTimeline($admin_id, Request $request)
    {
        try {
            $utils = new TimelineUtils();

            // $properties = Property::readPropertiesMinimum($admin_id);

            $page = (int) $request->get("page") ?? "";
            $perPage = 50;

            $filters = [
                'property' =>   (int) $request->get("property") ?? "",
                'harvest'  =>   (int) $request->get("harvest") ?? "",
                'user'     =>   (int) $request->get("user") ?? "",
                'crop'     =>   (int) $request->get("crop") ?? "",
                'date'     =>         $request->get("date") ?? "",
            ];

            $tablesToBeSearched = [
                'crops',
                'properties',

                // Apartir daqui são as tabelas utilizadas nos joins da query
                'properties_crops_diseases',
                'properties_crops_join',
                'properties_crops_observations',
                'properties_crops_pests',
                'properties_crops_rain_gauge',
                'properties_crops_stage',
                'properties_crops_weeds',
                'properties_management_data_population',
                'properties_management_data_harvest',
                'properties_management_data_inputs',
                'properties_management_data_seeds'
            ];

            $joinTables = array_slice($tablesToBeSearched, 2);

            $query = LogSystem::select(
                'log_system.id',
                'log_system.admin_id',
                'log_system.table_name',
                'log_system.operation',
                'log_system.object_id',
                'log_system.created_at',
                'log_system.to'
            )
                ->whereIn('log_system.table_name', $tablesToBeSearched)
                ->with('admin')
                ->orderBy('log_system.created_at', 'desc');

            foreach ($joinTables as $index => $table) {
                if ($table === 'properties_crops_join') {
                    $query->leftJoin($table, function ($join) use ($table) {
                        $join->on('log_system.table_name', '=', DB::raw("'{$table}'"))
                            ->on('log_system.object_id', '=', "{$table}.id");
                    });

                    continue;
                }

                $alias = "pcj_{$index}";

                $query->leftJoin("{$table} as {$table}_alias", function ($join) use ($table, $alias) {
                    $join->on('log_system.table_name', '=', DB::raw("'{$table}'"))
                        ->on('log_system.object_id', '=', "{$table}_alias.id")
                        ->leftJoin("properties_crops_join as {$alias}", "{$table}_alias.properties_crops_id", '=', "{$alias}.id");
                });
            }

            foreach ($filters as $name => $value) {
                if (empty($value)  || $name === 'date' || $name === 'user') {
                    continue;
                }

                $query->where(function ($query) use ($joinTables, $name, $value) {
                    $query->where(function ($query) use ($name, $value) {
                        $query->where("properties_crops_join.{$name}_id", '=', $value)
                            ->where('log_system.table_name', '=', 'properties_crops_join');
                    });

                    foreach ($joinTables as $index => $table) {
                        if ($table === 'properties_crops_join') {
                            continue;
                        }

                        $alias = "pcj_{$index}";

                        $query->orWhere(function ($query) use ($table, $alias, $name, $value) {
                            $query->where('log_system.table_name', '=', DB::raw("'{$table}'"))
                                ->where("{$alias}.{$name}_id", '=', $value);
                        });
                    }
                });
            }

            if ($filters['user']) {
                $query->where('log_system.admin_id', $filters['user']);
            }

            if ($filters['date']) {
                $query->whereDate('log_system.created_at', $filters['date']);
            }

            $total = $query->get()->count();
            $query = $query->skip(($page - 1) * $perPage)->take($perPage);

            $timeline = $query->get()
                ->groupBy(function ($item) {
                    return Carbon::parse($item->created_at)->format('d/m/Y');
                });

            $timeline = $utils->addFormattedLogInformations($timeline);
            // $timeline = $utils->filterLogs($timeline, $filters);


            return response()->json([
                'status' => 200,
                'timeline' => $timeline,
                'total' => $total,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search($admin_id, Request $request)
    {
        try {

            $properties = Property::with("admin")->with('crops')->where('status', '!=', 0)->orderBy("name", "ASC");

            // se o usuário que está lendo não for admin, somente os itens cadastrados por ele serão lidos
            $admin = Admin::find($admin_id);
            if ($admin->access_level != 1) {
                $properties->where("admin_id", $admin_id);
            }

            $properties = $properties->get();

            $crops = Crop::where("status", 1)->whereIn('property_id', $properties->pluck("id"))->orderBy("name", "ASC")->get();
            $harvests = Harvest::where("status", 1)->orderBy("name", "ASC")->get();

            $crop = $request->get('crop_id') ? Crop::readCrop($request->get('crop_id')) : null;
            $harvest = $admin->actual_harvest_id ? Harvest::readHarvest($admin->actual_harvest_id) : Harvest::with('crops_join')->where('is_last_harvest', 1)->where('status', 1)->orderBy("id", "DESC")->first();
            $property = $request->get('property_id') ? Property::readProperty($request->get('property_id'), $harvest->id) : null;

            $harvest->properties = Property::where("status", 1)->whereIn('id', $harvest->crops_join->pluck("property_id"))->orderBy("name", "ASC")->get();

            foreach ($harvest->properties as $property_loop) {
                $property_loop->crops = Crop::select('*')->where("status", 1)->where("property_id", $property_loop->id)->orderBy("name", "ASC")->get();
            }

            // dd($harvest);

            return response()->json([
                'status' => 200,
                'properties' => $properties,
                'crops' => $crops,
                'harvests' => $harvests,
                'property' => $property,
                'crop' => $crop,
                'harvest' => $harvest,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


// $query = LogSystem::select('id', 'admin_id', 'table_name', 'operation', 'object_id', 'created_at', 'to')
            //     ->whereIn('table_name', $tablesToBeSearched)
            //     ->orderBy("created_at", "DESC")
            //     ->with('admin');

            // $query->where(function ($query) use ($filters, $tablesToBeSearched) {
            //     if (isset($filters['harvest'])) {

            //         $filteredTablesToBeSearched = array_filter($tablesToBeSearched, function ($table){
            //             return $table !== 'properties_crops_join';
            //         });

            //         $query->where(function ($q) use ($filters, $filteredTablesToBeSearched) {
            //             // Para a tabela 'properties_crops_join'
            //             $q->where(function ($subQ) use ($filters) {
            //                 $subQ->where('table_name', 'properties_crops_join')
            //                     ->join('properties_crops_join', 'properties_crops_join.id', '=', 'log_system.object_id')
            //                     ->where('properties_crops_join.harvest_id', $filters['harvest']);
            //             });
                
            //             // Para as outras tabelas
            //             $q->orWhere(function ($subQ) use ($filters, $filteredTablesToBeSearched) {
            //                 foreach ($filteredTablesToBeSearched as $table) {
            //                     $subQ->orWhere('table_name', $table)
            //                         ->join($table, "$table.id", '=', 'log_system.object_id')
            //                         ->join('properties_crops_join', 'properties_crops_join.id', '=', "$table.properties_crops_join_id")
            //                         ->where('properties_crops_join.harvest_id', $filters['harvest']);
            //                 }
            //             });
            //         });

            //     }
            // });

            // $query = LogSystem::select('log_system.id', 'log_system.admin_id', 'log_system.table_name', 'log_system.operation', 'log_system.object_id', 'log_system.created_at', 'log_system.to')
            //     ->whereIn('log_system.table_name', array_merge($tablesToBeSearched, ['properties_crops_join']))
            //     ->orderBy("log_system.created_at", "DESC")
            //     ->with('admin');

            // // Join na tabela properties_crops_join
            // $query->join('properties_crops_join', function($join) use ($tablesToBeSearched) {
            //     $join->on('properties_crops_join.id', '=', 'log_system.object_id')
            //         ->where('log_system.table_name', 'properties_crops_join');
            // });

            // // Join nas outras tabelas, somente se não for properties_crops_join
            // foreach ($tablesToBeSearched as $table) {
            //     $query->orWhere(function ($q) use ($table) {
            //         $q->where('log_system.table_name', $table)
            //             ->join($table, "$table.id", '=', 'log_system.object_id')
            //             ->join('properties_crops_join', 'properties_crops_join.id', '=', "$table.properties_crops_join_id");
            //     });
            // }

            // // Aplicando filtro por harvest_id
            // $query->where(function ($q) use ($filters) {
            //     if (isset($filters['harvest'])) {
            //         $q->where('properties_crops_join.harvest_id', $filters['harvest']);
            //     }
            // });