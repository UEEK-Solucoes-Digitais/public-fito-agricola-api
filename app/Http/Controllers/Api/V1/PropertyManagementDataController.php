<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Admin;
use App\Models\Harvest;
use App\Models\StockExit;
use Illuminate\Http\Request;
use App\Models\StockIncoming;
use App\Models\PropertyCropJoin;
use App\Http\Controllers\Controller;
use App\Exceptions\OperationException;
use App\Models\Culture;
use App\Models\Defensive;
use App\Models\Fertilizer;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Models\PropertyManagementDataSeed;
use App\Models\PropertyManagementDataInput;
use App\Models\PropertyManagementDataHarvest;
use App\Models\PropertyManagementDataPopulation;
use App\Models\Stock;
use Illuminate\Support\Carbon;

class PropertyManagementDataController extends Controller
{

    /**
     * @param property_crop_join_id
     * @param type (harvest, fertilizer, defensive, population, seed)
     * @return \Illuminate\Http\JsonResponse
     * @throws OperationException
     */

    public function list($property_crop_join_id, $admin_id, $type)
    {
        try {
            $management_data = [];
            $products = Product::orderBy('name', 'asc')->where('status', 1);

            // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
            $admin = Admin::find($admin_id);
            if ($admin->access_level != 1) {
                $products = $products->whereIn('admin_id', [0, $admin_id]);
            }

            // $crop_join = PropertyCropJoin::find($property_crop_join_id);

            // $products = $products->whereHas('stock', function ($q) use ($crop_join) {
            //     $q->where('property_id', $crop_join->property_id)
            //         ->orWhereNull('property_id');
            // });

            $data_seeds = [];

            switch ($type) {
                case 'harvest':
                    $data_seeds = PropertyManagementDataSeed::readDataSeedByJoin($property_crop_join_id);

                    $management_data = PropertyManagementDataHarvest::readDataHarvestByCropJoin($property_crop_join_id);
                    break;
                case 'fertilizer':
                    $management_data = PropertyManagementDataInput::readDataInputByCropJoin($property_crop_join_id, 1);

                    $products = $products->where('type', 3);
                    break;
                case 'defensive':
                    $management_data = PropertyManagementDataInput::readDataInputByCropJoin($property_crop_join_id, 2);



                    $products = $products->where('type', 2);

                    break;
                case 'population':
                    $data_seeds = PropertyManagementDataSeed::readDataSeedByJoin($property_crop_join_id);
                    $management_data = PropertyManagementDataPopulation::readDataPopulationByCropJoinId($property_crop_join_id, 2);
                    break;
                case 'seed':
                    $management_data =  PropertyManagementDataSeed::readDataSeedByJoin($property_crop_join_id);

                    $products = $products->where('type', 1);
                    break;
            }

            $products = $products->get();

            // foreach ($products as $item) {
            //     switch ($item->stock->product->type) {
            //         case 1:
            //             $item->stock->product->seed = Culture::where('status', '!=', 0)->where('id', $item->stock->product->item_id)->first();
            //             break;
            //         case 2:
            //             $item->stock->product->defensive = Defensive::where('status', '!=', 0)->where('id', $item->stock->product->item_id)->first();
            //             break;

            //         case 3:
            //             $item->stock->product->fertilizer = Fertilizer::where('status', '!=', 0)->where('id', $item->stock->product->item_id)->first();
            //             break;
            //     }
            // }

            return response()->json([
                'status' => 200,
                'management_data' => $management_data,
                'products' => $products,
                'data_seeds' => $data_seeds,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function form(Request $request)
    {
        switch ($request->type) {
            case 'harvest':
                return self::formHarvest($request);
                break;
            case 'fertilizer':

                if ($request->products_id) {
                    $validated = true;
                    foreach ($request->products_id as $key => $product_id) {
                        if ($product_id != 0 && $product_id != null && $product_id != "") {
                            $request->request->remove('product_id');
                            $request->merge(['product_id' => $product_id]);

                            $request->request->remove('dosage');
                            $request->merge(['dosage' => $request->dosages[$key] ?? 0]);

                            $operation = self::formInput($request, true);

                            if (!$operation) {
                                $validated = false;
                            };
                        }
                    }

                    if ($validated) {
                        return response()->json([
                            'status' => 200,
                            'msg' => "Operação realizada com sucesso",
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 500,
                            'msg' => "Não foi possível realizar a operação no momento",
                        ], 500);
                    }
                } else {
                    return self::formInput($request);
                }
                break;
            case 'defensive':
                if ($request->products_id) {
                    $validated = true;
                    foreach ($request->products_id as $key => $product_id) {
                        if ($product_id != 0 && $product_id != null && $product_id != "") {
                            $request->request->remove('product_id');
                            $request->merge(['product_id' => $product_id]);

                            $request->request->remove('dosage');
                            $request->merge(['dosage' => $request->dosages[$key] ?? 0]);

                            $operation = self::formInput($request, true);

                            if (!$operation) {
                                $validated = false;
                            };
                        }
                    }

                    if ($validated) {
                        return response()->json([
                            'status' => 200,
                            'msg' => "Operação realizada com sucesso",
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 500,
                            'msg' => "Não foi possível realizar a operação no momento",
                        ], 500);
                    }
                } else {
                    return self::formInput($request);
                }

                break;
            case 'population':
                return self::formPopulation($request);
                break;
            case 'seed':
                return self::formSeed($request);
                break;
        }
    }

    public function multipleForm(Request $request)
    {

        // return response()->json([
        //     'status' => 409,
        //     'msg' => "Recurso indisponível no momento",
        // ], 409);

        try {
            $success = [];
            $failes = [];


            // $harvest = $request->harvest_id ? Harvest::find($request->harvest_id) : Harvest::where('status', 1)->where('is_last_harvest', 1)->first();
            foreach ($request->crops as $crop) {
                $property_crop_join = PropertyCropJoin::find(isset($crop['id']) ? $crop['id'] : $crop);

                if ($property_crop_join) {
                    $request->request->remove('properties_crops_id');
                    $request->merge(['properties_crops_id' => $property_crop_join->id]);

                    switch ($request->type) {
                        case 'harvest':
                            $operation = self::formHarvest($request, true);

                            if ($operation) {
                                array_push($success, $request->properties_crops_id);
                            } else {
                                array_push($failes, $request->properties_crops_id);
                            };

                            break;
                        case 'fertilizer':
                            foreach ($request->products_id as $key => $product_id) {
                                if ($product_id != 0 && $product_id != null && $product_id != "") {
                                    $request->request->remove('product_id');
                                    $request->merge(['product_id' => $product_id]);

                                    $request->request->remove('dosage');
                                    $request->merge(['dosage' => $request->dosages[$key] ?? 0]);

                                    $operation = self::formInput($request, true);

                                    if ($operation) {
                                        array_push($success, $request->properties_crops_id);
                                    } else {
                                        array_push($failes, $request->properties_crops_id);
                                    };
                                }
                            }

                            break;
                        case 'defensive':
                            foreach ($request->products_id as $key => $product_id) {
                                if ($product_id != 0 && $product_id != null && $product_id != "") {
                                    $request->request->remove('product_id');
                                    $request->merge(['product_id' => $product_id]);

                                    $request->request->remove('dosage');
                                    $request->merge(['dosage' => $request->dosages[$key] ?? 0]);

                                    $operation = self::formInput($request, true);

                                    if ($operation) {
                                        array_push($success, $request->properties_crops_id);
                                    } else {
                                        array_push($failes, $request->properties_crops_id);
                                    };
                                }
                            }

                            break;
                        case 'seed':
                            $operation = self::formSeed($request, true);

                            if ($operation) {
                                array_push($success, $request->properties_crops_id);
                            } else {
                                array_push($failes, $request->properties_crops_id);
                            };

                            break;
                    }
                } else {
                    array_push($failes, $crop);
                }
            }

            if (count($failes) == 0) {
                return response()->json([
                    'status' => 200,
                    'msg' => "Operação realizada com sucesso",
                ], 200);
            } else {
                $text = json_encode($failes);
                throw new OperationException('Erro ao cadastrar/editar lançamento (registro de atividades)', PropertyManagementDataSeed::getTableName(), "IDs: {$text}", 500);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }


    /**
     * @param admin_id
     * @param stock_incoming_id
     * @param properties_crops_id
     * @param kilogram_per_ha
     * @param spacing
     * @param seed_per_linear_meter
     * @param pms
     * @return \Illuminate\Http\JsonResponse
     * @throws OperationException
     */

    public static function formSeed(Request $request, $bool = false)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'product_id' => 'required',
                'properties_crops_id' => 'required',
                'date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar semente (dados de manejo)', PropertyManagementDataSeed::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $data_seed = PropertyManagementDataSeed::find($request->id);

                if (!$data_seed) {
                    throw new OperationException('Erro ao ler semente (dados de manejo) na operação de edição', PropertyManagementDataSeed::getTableName(), "Semente não encontrada: {$request->id}", 409);
                }
            } else {
                $data_seed = new PropertyManagementDataSeed();
            }

            $data_seed->kilogram_per_ha = floatval(isString($request->kilogram_per_ha));
            $data_seed->spacing = floatval(isString($request->spacing));
            $data_seed->seed_per_linear_meter = floatval(isString($request->seed_per_linear_meter));
            $data_seed->pms = floatval(isString($request->pms));
            $data_seed->cost_per_kilogram = floatval(isString($request->cost_per_kilogram));

            $area = $request->area;

            if ($bool && count($request->crops) > 1) {
                $crop_join = PropertyCropJoin::find($request->properties_crops_id);
                $area = $crop_join->crop->area - $crop_join->data_seed->sum('area');
            }

            if ($request->convert_to_alq) {
                $data_seed->area = $area > 0 ? floatval(isString($area))  / 2.42 : 0;
            } else {
                $data_seed->area = $area > 0 ? floatval(isString($area))  : 0;
            }
            // $data_seed->dosage = floatval(str_replace(",", ".", $request->dosage));

            // custo por hectare
            $data_seed->product_id = $request->product_id;
            $data_seed->product_variant = $request->culture_code;
            $data_seed->properties_crops_id = $request->properties_crops_id;
            $data_seed->date = $request->date;

            // metro quadrado
            $data_seed->seed_per_square_meter = $data_seed->spacing != 0 ? $data_seed->seed_per_linear_meter / $data_seed->spacing : 0;
            $data_seed->quantity_per_ha = $data_seed->seed_per_square_meter * 10000;
            $data_seed->save();

            if (!$request->id) {
                self::addNotification($data_seed, 'seed');
            } else {
                // alterando informações das populações vinculadas
                foreach ($data_seed->data_population as $data_population) {
                    $data_population->seed_per_square_meter = $data_seed->spacing > 0 ? $data_population->seed_per_linear_meter / $data_seed->spacing : 0;
                    $data_population->emergency_percentage = $data_seed->seed_per_linear_meter > 0 ? ($data_population->seed_per_linear_meter * 100) / $data_seed->seed_per_linear_meter : 0;
                    $data_population->quantity_per_ha = $data_population->seed_per_square_meter * 10000;
                    $data_population->plants_per_hectare = $data_population->seed_per_square_meter * 10000;
                    $data_population->save();
                }
            }

            $text = $request->id ? 'editada' : 'cadastrada';

            // diminuindo estoque
            try {
                $join = PropertyCropJoin::find($request->properties_crops_id);

                $stock = Stock::where('product_id', $request->product_id)->where("product_variant", $request->culture_code)->where("property_id", $join->property_id)->first();


                if (!$stock) {
                    $stock = new Stock();
                    $stock->product_id = $request->product_id;
                    $stock->product_variant = $request->culture_code;
                    $stock->property_id = $join->property_id;
                    $stock->save();
                }
                $stock_exits = null;

                if ($request->id) {
                    $stock_exits = StockExit::where('object_id', $request->id)->first();
                }

                if (!$stock_exits) {
                    $stock_exits = new StockExit();
                }
                $stock_exits->properties_crops_id = $request->properties_crops_id;
                $stock_exits->stock_id = isset($stock) && $stock ? $stock->id : null;
                $stock_exits->quantity = $data_seed->kilogram_per_ha * $data_seed->area;
                $stock_exits->type = 'seed';
                $stock_exits->object_id = $data_seed->id;
                $stock_exits->save();
            } catch (\Exception $e) {
                report($e);
            }

            if ($bool) {
                return true;
            }

            return response()->json([
                'status' => 200,
                'msg' => "Semente {$text} com sucesso",
                'data_seed' => $data_seed
            ], 200);
        } catch (OperationException $e) {
            report($e);

            if ($bool) {
                return false;
            }

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * @param admin_id
     * @param property_crop_join_id
     * @param properties_crops_id
     * @param emergency_percentage_date
     * @param seed_per_linear_meter
     * @return \Illuminate\Http\JsonResponse
     * @throws OperationException
     */

    public static function formPopulation(Request $request, $bool = false)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_management_data_seed_id' => 'required',
                'properties_crops_id' => 'required',
                'seed_per_linear_meter' => 'required',
                'emergency_percentage_date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar população (dados de manejo)', PropertyManagementDataPopulation::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $data_population = PropertyManagementDataPopulation::find($request->id);

                if (!$data_population) {
                    throw new OperationException('Erro ao ler população (dados de manejo) na operação de edição', PropertyManagementDataPopulation::getTableName(), "População não encontrad: {$request->id}", 409);
                }
            } else {
                $data_population = new PropertyManagementDataPopulation();
            }

            $data_population->property_management_data_seed_id = $request->property_management_data_seed_id;
            $data_population->properties_crops_id = $request->properties_crops_id;
            $data_population->emergency_percentage_date = $request->emergency_percentage_date;
            $data_population->seed_per_linear_meter = floatval(str_replace(",", ".", $request->seed_per_linear_meter));

            // por m²
            $data_seed = PropertyManagementDataSeed::find($request->property_management_data_seed_id);
            $data_population->seed_per_square_meter = $data_seed->spacing > 0 ? $data_population->seed_per_linear_meter / $data_seed->spacing : 0;

            // % de emergencia
            $data_population->emergency_percentage = $data_seed->seed_per_linear_meter > 0 ? ($data_population->seed_per_linear_meter * 100) / $data_seed->seed_per_linear_meter : 0;

            // quantidade por hectare
            $data_population->quantity_per_ha = $data_population->seed_per_square_meter * 10000;
            $data_population->plants_per_hectare = $data_population->seed_per_square_meter * 10000;
            $data_population->save();
            if (!$request->id) {
                self::addNotification($data_population, 'population');
            }

            $text = $request->id ? 'editado' : 'cadastrado';

            if ($bool) {
                return true;
            }

            return response()->json([
                'status' => 200,
                'msg' => "População {$text} com sucesso",
                'data_population' => $data_population
            ], 200);
        } catch (OperationException $e) {
            report($e);

            if ($bool) {
                return false;
            }

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * @param admin_id
     * @param stock_incoming_id
     * @param properties_crops_id
     * @param date
     * @param dosage
     * @return \Illuminate\Http\JsonResponse
     * @throws OperationException
     */

    public static function formInput(Request $request, $bool = false)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'properties_crops_id' => 'required',
                // 'product_id' => 'required',
                'date' => 'required',
                'dosage' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar defensivo/fertilizante (dados de manejo)', PropertyManagementDataInput::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $data_input = PropertyManagementDataInput::find($request->id);

                if (!$data_input) {
                    throw new OperationException('Erro ao ler defensivo/fertilizante (dados de manejo) na operação de edição', PropertyManagementDataInput::getTableName(), "População não encontrad: {$request->id}", 409);
                }
            } else {
                $data_input = new PropertyManagementDataInput();
            }

            $data_input->type = $request->type == "fertilizer" ? 1 : 2;
            $data_input->properties_crops_id = $request->properties_crops_id;
            $data_input->product_id = $request->product_id;
            $data_input->date = $request->date;
            $data_input->dosage = floatval(isString($request->dosage));
            $data_input->save();

            $title = $request->type == "fertilizer" ? "Fertilizante" : "Defensivo";
            $text = $request->id ? 'editado' : 'cadastrado';

            if (!$request->id) {
                self::addNotification($data_input, $request->type);
            }

            // diminuindo estoque
            try {

                $join = PropertyCropJoin::find($request->properties_crops_id);

                $stock = Stock::where('product_id', $request->product_id)->where("property_id", $join->property_id)->first();

                if (!$stock) {
                    $stock = new Stock();
                    $stock->product_id = $request->product_id;
                    $stock->property_id = $join->property_id;
                    $stock->save();
                }

                // $stock_incoming = $stock->stock_incomings->first();

                // if (!$stock_incoming) {
                //     $stock_incoming = new StockIncoming();
                //     $stock_incoming->stock_id = $stock->id;
                //     $stock_incoming->value = 0;
                //     $stock_incoming->quantity = 0;
                //     $stock_incoming->quantity_unit = 2;
                //     $stock_incoming->save();
                // }

                $property_crop_join = PropertyCropJoin::find($request->properties_crops_id);

                $stock_exits = null;

                if ($request->id) {
                    $stock_exits = StockExit::where('object_id', $request->id)->first();
                }

                if (!$stock_exits) {
                    $stock_exits = new StockExit();
                }
                $stock_exits->properties_crops_id = $request->properties_crops_id;
                $stock_exits->stock_id = $stock->id;
                $stock_exits->quantity = $data_input->dosage * $property_crop_join->crop->area;
                $stock_exits->type = $data_input->type == 1 ? 'fertilizer' : 'defensive';
                $stock_exits->object_id = $data_input->id;
                $stock_exits->save();
            } catch (\Exception $e) {
                report($e);
            }

            if ($bool) {
                return true;
            }

            return response()->json([
                'status' => 200,
                'msg' => "{$title} {$text} com sucesso",
                'data_input' => $data_input
            ], 200);
        } catch (OperationException $e) {
            report($e);

            if ($bool) {
                return false;
            }

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function formHarvest(Request $request, $bool = false)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'properties_crops_id' => 'required',
                'total_production' => 'required',
                'date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar colheita (dados de manejo)', PropertyManagementDataHarvest::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $data_input = PropertyManagementDataHarvest::find($request->id);

                if (!$data_input) {
                    throw new OperationException('Erro ao ler colheita (dados de manejo) na operação de edição', PropertyManagementDataHarvest::getTableName(), "População não encontrad: {$request->id}", 409);
                }
            } else {
                $data_input = new PropertyManagementDataHarvest();
            }

            $data_input->properties_crops_id = $request->properties_crops_id;
            $data_input->property_management_data_seed_id = $request->property_management_data_seed_id && $request->property_management_data_seed_id != 0 ? $request->property_management_data_seed_id : null;
            $data_input->date = $request->date;
            $data_input->without_harvest = $request->without_harvest ?? 0;
            $data_input->total_production = isString($request->total_production);

            // produtividade
            if ($request->property_management_data_seed_id && $request->property_management_data_seed_id != 0) {
                $seed = PropertyManagementDataSeed::find($request->property_management_data_seed_id);

                if ($seed) {
                    $data_input->productivity = $data_input->total_production / $seed->area;
                } else {
                    $data_input->productivity = 0;
                }
            } else {
                $crop = PropertyCropJoin::find($request->properties_crops_id);
                $data_input->productivity = $data_input->total_production / $crop->crop->area;
            }

            $data_input->save();

            if (!$request->id) {
                self::addNotification($data_input, 'harvest');
            }

            PropertyCropJoin::where('id', $request->properties_crops_id)->update(['is_harvested' => 1]);

            $text = $request->id ? 'editada' : 'cadastrada';

            if ($bool) {
                return true;
            }

            return response()->json([
                'status' => 200,
                'msg' => "Colheita {$text} com sucesso",
                'data_input' => $data_input
            ], 200);
        } catch (OperationException $e) {
            report($e);

            if ($bool) {
                return false;
            }

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function delete($type, Request $request)
    {
        try {
            checkSection($request->admin_id);

            switch ($type) {
                case 'harvest':
                    $item = PropertyManagementDataHarvest::find($request->id);
                    $item->update(['status' => 0]);

                    $property_crop_join = PropertyCropJoin::find($item->properties_crops_id);

                    if ($property_crop_join->data_harvest->count() == 0) {
                        $property_crop_join->update(['is_harvested' => 0]);
                    }
                    break;
                case 'fertilizer':
                    PropertyManagementDataInput::find($request->id)->update(['status' => 0]);
                    break;
                case 'defensive':
                    PropertyManagementDataInput::find($request->id)->update(['status' => 0]);
                    break;
                case 'population':
                    PropertyManagementDataPopulation::find($request->id)->update(['status' => 0]);
                    break;
                case 'seed':
                    PropertyManagementDataSeed::find($request->id)->update(['status' => 0]);
                    break;
            }

            // se for semente, fertilizante ou defensivo deve tirar do estoque
            if ($type == 'seed' || $type == 'fertilizer' || $type == 'defensive') {
                $stock_exit = StockExit::where('object_id', $request->id)->first();

                if ($stock_exit) {
                    $stock_exit->delete();
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Entrada removida com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function addNotification($object, $type)
    {
        try {
            if ($type == 'seed') {
                $text_to_add = "Semente";
            }

            if ($type == 'fertilizer') {
                $text_to_add = "Fertilizante";
            }

            if ($type == 'defensive') {
                $text_to_add = "Defensivo";
            }

            if ($type == 'population') {
                $text_to_add = "População";
            }

            if ($type == 'harvest') {
                $text_to_add = "Colheita";
            }

            $title = "{$object->property_crop->property->name} - {$object->property_crop->crop->name} - {$text_to_add}";
            $text = "Clique para entrar na propriedade.";

            $admin_section = session(config('app.session_name') . "_admin_id");
            $is_equal_admin = false;

            if ($object->property_crop->property->admin->id == $admin_section) {
                $is_equal_admin = true;
            }

            foreach ($object->property_crop->property->admins as $admin) {
                if ($admin->id == $admin_section) {
                    $is_equal_admin = true;
                }

                createNotification($title, $text, 0, $admin->id, $object->property_crop->id, "management-data", "&subtype={$type}", 0, '', $type);
            }

            createNotification($title, $text, 0, $object->property_crop->property->admin->id, $object->property_crop->id, "management-data", "&subtype={$type}", 0, '', $type);

            if (!$is_equal_admin) {
                createNotification($title, $text, 0, $admin_section, $object->property_crop->id, "management-data", "&subtype={$type}", 0, '', $type);
            }
        } catch (OperationException $e) {
            report($e);
        }
    }

    public function getArea(Request $request)
    {
        try {
            $total_area = 0;
            $total_used_area = 0;

            $property_crop_join = PropertyCropJoin::whereIn('id', explode(',', $request->crops_ids))
                ->where("status", 1)
                ->get();

            foreach ($property_crop_join as $item) {
                $total_area += $item->crop->area;
                $total_used_area += $item->data_seed->sum('area');
            }

            return response()->json([
                'status' => 200,
                'total_area' => $total_area,
                'total_used_area' => $total_used_area,
                'total_remaining_area' => $total_area - $total_used_area,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }
}
