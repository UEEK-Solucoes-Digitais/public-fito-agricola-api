<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Harvest;
use App\Models\Product;
use App\Models\Property;
use App\Models\PropertyCropJoin;
use App\Models\PropertyManagementDataHarvest;
use App\Models\PropertyManagementDataInput;
use App\Models\PropertyManagementDataPopulation;
use App\Models\PropertyManagementDataSeed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

ini_set('max_execution_time', 500);

class OfflineController extends Controller
{
    public function getFirstPart($admin_id, Request $request)
    {
        $harvest_query = "";

        // if ($request->get('harvest_id')) {
        //     $harvest_query = "?harvest_id={$request->get('harvest_id')}&harvests_id={$request->get('harvest_id')}";
        // }

        $route_list = [
            route('admin.list', ['admin_id' => $admin_id]),
            route("properties.list", ['admin_id' => $admin_id]),
            route("harvests.list"),
            route("crops.list", ['admin_id' => $admin_id]),
            route("dashboard.getCrops", ['admin_id' => $admin_id]),
            route("dashboard.getItens", ['admin_id' => $admin_id]) . "?filter=simple&with_join=true",
            route('interference_factors.list', ['type' => 1]),
            route("cultures.list", ['admin_id' => $admin_id]),
            route("defensives.list", ['admin_id' => $admin_id]),
            route("fertilizers.list", ['admin_id' => $admin_id]),
        ];

        $routes = [];

        foreach ($route_list as $route_to_add) {
            $routes[$route_to_add] = self::getResponse($route_to_add);
        }

        return response()->json([
            'routes' => $routes
        ]);
    }

    public function getSecondPart($admin_id, Request $request)
    {
        $routes = [];

        $harvest_query = "";
        $harvest_query_used = "";

        // if ($request->get('harvest_id')) {
        //     $harvest_query = "?harvest_id={$request->get('harvest_id')}&harvests_id={$request->get('harvest_id')}";
        //     $harvest_query_used = "&harvest_id={$request->get('harvest_id')}&harvests_id={$request->get('harvest_id')}";
        // }

        list($properties, $total) = Property::readProperties($admin_id, null, null, ['id', 'name', 'city', 'status', 'admin_id']);
        list($harvests, $total) = Harvest::readHarvests(null, null);
        list($products, $total) = Product::readProducts($admin_id, null, null, null, ['id', 'name', 'type', 'extra_column', 'object_type']);

        if ($request->get('properties_id')) {
            $properties = $properties->whereIn('id', explode(',', $request->get('properties_id')));
        }

        $admin = Admin::find($admin_id);

        $last_harvest = $harvests->where('is_last_harvest', 1)->first();

        foreach ($properties as $property) {

            $property_read = self::getResponse(route('properties.read', ['id' => $property->id]));

            $routes_property = [
                route('properties.read', ['id' => $property->id]) . "?read_miminum=true",
                route('properties.read', ['id' => $property->id]) . "?read_simple=true",
            ];

            $all_joins = PropertyCropJoin::select('properties_crops_join.*',)->whereHas('crop')->with(['crop' => function ($q) {
                $q->select("id", "name", "area");
            }])->join('crops', 'crops.id', '=', 'properties_crops_join.crop_id')
                ->where('crops.status', 1)
                ->where('properties_crops_join.property_id', $property->id)
                ->where('properties_crops_join.status', 1)
                ->orderBy('crops.name', 'asc')
                ->get();


            foreach ($harvests as $harvest) {
                // dd($property);

                $routes[route('properties.readCropsByOptions') . "?property_id={$property->id}&harvest_id={$harvest->id}"] = [
                    'status' => 200,
                    'joins' => $all_joins->where('harvest_id', $harvest->id)->values(),
                ];

                $routes[route('properties.readLinkedCrops', ['id' => $property->id]) . "?harvest_id={$harvest->id}"] = self::getResponse(route('properties.readLinkedCrops', ['id' => $property->id]) . "?harvest_id={$harvest->id}");
            }

            foreach ($routes_property as $route_to_add) {
                $routes[$route_to_add] = $property_read;
            }

            // dd($all_joins);


            foreach ($all_joins->where('harvest_id', ($admin->actual_harvest_id ?? $harvests->where('is_last_harvest', 1)->first()->id)) as $crop_join) {
                // last_plant_rain_gauges
                // last_plant_disease
                // end_plant_rain_gauges

                $rain_gauges = self::getResponse(route('rain_gauge.listMobile', ['property_crop_join_id' => $crop_join->id]));
                $rain_gauges_decode = json_decode($rain_gauges, true);

                $routes_join = [
                    route('interference_factors.listByJoin', ['crop_join_id' => $crop_join->id]),

                ];

                if ($rain_gauges_decode) {
                    $routes_join[] = route('properties.filterRainGauge', ['property_crop_join_id' => $crop_join->id, 'type' => 'custom', 'begin' => $rain_gauges_decode['last_plant_rain_gauges'], 'end' => $rain_gauges_decode['last_plant_disease']]) . $harvest_query;
                    $routes_join[] = route('properties.filterDisease', ['property_crop_join_id' => $crop_join->id, 'type' => 'custom', 'begin' => $rain_gauges_decode['last_plant_disease'], 'end' => (new \DateTime(Carbon::now()))->format("Y-m-d")]) . $harvest_query;
                }

                $routes[route('rain_gauge.listMobile', ['property_crop_join_id' => $crop_join->id]) . $harvest_query] = $rain_gauges;

                if ((!$admin->actual_harvest_id && $last_harvest->id == $crop_join->harvest_id) || ($admin->actual_harvest_id && $crop_join->harvest_id == $admin->actual_harvest_id)) {
                    $routes[route('properties.readPropertyCropJoin') . "?crop_id={$crop_join->crop_id}&admin_id={$admin_id}"] = [
                        'status' => 200,
                        'property_crop_join' => $crop_join,
                    ];
                }

                // $routes[route('properties.readPropertyCropJoin') . "?join_id={$crop_join->id}"] = [
                //     'status' => 200,
                //     'property_crop_join' => $crop_join,
                // ];

                $crop_join->crop = $crop_join->crop;
                $crop_join->harvest = $crop_join->harvest;

                $data_seeds = PropertyManagementDataSeed::readDataSeedByJoin($crop_join->id);
                $data_harvest = PropertyManagementDataHarvest::readDataHarvestByCropJoin($crop_join->id);
                $data_fertilizer = PropertyManagementDataInput::readDataInputByCropJoin($crop_join->id, 1);
                $data_defensive = PropertyManagementDataInput::readDataInputByCropJoin($crop_join->id, 2);
                $data_population = PropertyManagementDataPopulation::readDataPopulationByCropJoinId($crop_join->id, 2);

                $routes[route('properties.readPropertyHarvest', ['property_crop_join_id' => $crop_join->id]) . "?with_draw_area=false"] = [
                    'status' => 200,
                    'property' => $crop_join->property()->with(['crops' => function ($q) use ($crop_join) {
                        $q->where('harvest_id', $crop_join->harvest_id)->with('crop');
                    }])->first(),
                    'join' => $crop_join,
                    'crop' => $crop_join->crop,
                    'harvest' => $crop_join->harvest,
                ];

                $routes[route('properties.managementData.list', ['admin_id' => $admin_id, 'property_crop_join_id' => $crop_join->id, 'type' => 'seed']) . $harvest_query] = json_encode([
                    'status' => 200,
                    'management_data' => $data_seeds,
                    'products' => $products->where('type', 1)->values(),
                    'data_seeds' => $data_seeds,
                ]);


                $routes[route('properties.managementData.list', ['admin_id' => $admin_id, 'property_crop_join_id' => $crop_join->id, 'type' => 'population']) . $harvest_query] = [
                    'status' => 200,
                    'management_data' => $data_population,
                    'products' => [],
                    'data_seeds' => $data_seeds,
                ];
                $routes[route('properties.managementData.list', ['admin_id' => $admin_id, 'property_crop_join_id' => $crop_join->id, 'type' => 'harvest']) . $harvest_query] = [
                    'status' => 200,
                    'management_data' => $data_harvest,
                    'products' => [],
                    'data_seeds' => $data_seeds,
                ];


                $routes[route('properties.managementData.list', ['admin_id' => $admin_id, 'property_crop_join_id' => $crop_join->id, 'type' => 'fertilizer']) . $harvest_query] = [
                    'status' => 200,
                    'management_data' => $data_fertilizer,
                    'products' => $products->where('type', 3)->values(),
                    'data_seeds' => [],
                ];

                $routes[route('properties.managementData.list', ['admin_id' => $admin_id, 'property_crop_join_id' => $crop_join->id, 'type' => 'defensive']) . $harvest_query] = [
                    'status' => 200,
                    'management_data' => $data_defensive,
                    'products' => $products->where('type', 2)->values(),
                    'data_seeds' => [],
                ];

                $management_data = Property::readManagementData($crop_join->id);

                $routes[route('properties.monitoring.list', ['property_crop_join_id' => $crop_join->id]) . $harvest_query] = [
                    'status' => 200,
                    'management_data' => $management_data,
                    'crop' => $crop_join->crop,
                ];

                foreach ($routes_join as $route_to_add) {
                    $routes[$route_to_add] = self::getResponse($route_to_add);
                }
            }
        }

        return response()->json([
            'routes' => $routes
        ]);
    }

    public function getPartialSync($admin_id, Request $request)
    {
        try {

            $route_list = [
                route("dashboard.getCrops", ['admin_id' => $admin_id]),
                route("properties.list", ['admin_id' => $admin_id]),
            ];

            list($properties, $total) = Property::readProperties($admin_id, null, null, ['id', 'name', 'city', 'status', 'admin_id']);

            if ($request->get('properties_ids') && $request->get('properties_ids') != 'null' && $request->get('properties_ids') != '') {
                $properties = $properties->whereIn('id', explode(',', $request->get('properties_ids')));
            }

            $admin = Admin::find($admin_id);

            foreach ($route_list as $route_to_add) {
                $routes[$route_to_add] = self::getResponse($route_to_add);
            }


            foreach ($properties as $property) {
                $all_joins = PropertyCropJoin::select('properties_crops_join.*',)->whereHas('crop')->with(['crop' => function ($q) {
                    $q->select("id", "name", "area");
                }])->join('crops', 'crops.id', '=', 'properties_crops_join.crop_id')
                    ->where('crops.status', 1)
                    ->where('properties_crops_join.property_id', $property->id)
                    ->where('properties_crops_join.status', 1)
                    ->orderBy('crops.name', 'asc')
                    ->get();

                foreach ($all_joins as $crop_join) {
                    if ((!$admin->actual_harvest_id) || ($admin->actual_harvest_id && $crop_join->harvest_id == $admin->actual_harvest_id)) {
                        $routes[route('properties.readPropertyCropJoin') . "?crop_id={$crop_join->crop_id}&admin_id={$admin_id}"] = [
                            'status' => 200,
                            'property_crop_join' => $crop_join,
                        ];
                    }
                }
            }


            return response()->json([
                'routes' => $routes
            ]);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    static function getResponse($route_to_add)
    {
        $request = Request::create($route_to_add, 'GET');

        // Enviar a requisição criada e armazenar a resposta
        $response = app()->handle($request);
        $response = Route::dispatch($request);
        return $response->getContent();
    }
}
