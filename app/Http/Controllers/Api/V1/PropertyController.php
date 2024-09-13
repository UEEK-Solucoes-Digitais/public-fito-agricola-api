<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Crop;
use App\Models\Admin;
use App\Models\Harvest;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\PropertyCropJoin;
use App\Http\Controllers\Controller;
use App\Exceptions\OperationException;
use App\Models\Defensive;
use App\Models\PropertyCropDisease;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Symfony\Component\Console\Input\Input;

class PropertyController extends Controller
{

    /**
     * Lista as propriedades de um admin
     * @param admin_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            // $harvest_id = $request->get("harvest_id") ?? "";

            list($properties, $total) = Property::readProperties($admin_id, $filter, $page, ['id', 'name', 'city', 'status', 'admin_id'], true);

            return response()->json([
                'status' => 200,
                'properties' => $properties,
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

    /**
     * Lê uma propriedade
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function readPropertyCropJoin(Request $request)
    {
        try {

            // $property_id = $request->get("property_id") ?? "";
            // $harvest_id = $request->get("harvest_id") ?? "";
            // $crop_id = $request->get("crop_id") ?? "";

            // $property_crop_join = PropertyCropJoin::where('property_id', $property_id)->where('harvest_id', $harvest_id)->where('crop_id', $crop_id)->where("status", 1)->first();

            if ($request->get("crop_id")) {
                $crop = Crop::readCrop($request->get("crop_id"));

                if ($crop) {
                    $harvest = null;
                    if ($request->get("admin_id")) {
                        $admin = Admin::find($request->get("admin_id"));

                        if ($admin && $admin->actual_harvest_id) {
                            $harvest = Harvest::find($admin->actual_harvest_id);
                        }
                    }

                    if (!$harvest) {
                        $harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();
                    }

                    $property_crop_join = $crop->crops_join->where("harvest_id", $harvest->id)->first();
                }
            } else {
                $property_crop_join = PropertyCropJoin::find($request->get("join_id"));
            }

            return response()->json([
                'status' => 200,
                'property_crop_join' => $property_crop_join,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }
    public function read($id = null, Request $request)
    {
        try {
            if (!$id) {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "ID Não enviado", 422);
            }



            if ($request->get("read_minimum")) {
                $property = Property::find($id);
                $harvest = null;
                $is_last_harvest = null;
            } else {
                $harvest_id = $request->get("harvest_id") ?? "";
                $filter = $request->get("filter") ?? "";

                $property = Property::readProperty($id, $harvest_id, $filter);

                if ($harvest_id) {
                    $harvest = Harvest::find($harvest_id);
                    $is_last_harvest = $harvest->is_last_harvest ? true : false;
                } else {
                    $harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();
                    $is_last_harvest = true;
                }
            }

            if ($property) {
                if ($request->get("admin_id")) {
                    $admin = Admin::find($request->get("admin_id"));
                    $admin_properties = $admin->all_properties();
                    // verificando se a propriedade é do admin ou se está nos admins vinculados
                    if ($admin->access_level != 1 && $property->admin_id != $admin->id && !in_array($property->id, $admin_properties->pluck('id')->toArray())) {
                        return response()->json([
                            'status' => 200,
                            'not_allowed' => true,
                        ], 200);
                    }
                }

                return response()->json([
                    'status' => 200,
                    'property' => $property,
                    'harvest' => $harvest,
                    'isLastHarvert' => $is_last_harvest,
                ], 200);
            } else {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "Propriedade não encontrada: {$id}", 409);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Cadastra ou edita uma propriedade
     * @param admin_id
     * @param name
     * @param state_subscription
     * @param cep
     * @param street
     * @param neighborhood
     * @param uf
     * @param city
     * @param latitude
     * @param longitude
     * @return \Illuminate\Http\JsonResponse
     */
    public function form(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'name' => 'required',
                'uf' => 'required',
                'city' => 'required',
            ]);

            $admin = Admin::find($request->admin_id);

            if ($admin->all_properties_count() >= $admin->properties_available) {
                throw new OperationException('Erro ao cadastrar/editar propriedade', Property::getTableName(), "Limite de propriedades atingido ({$admin->properties_available})", 422);
            }

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar propriedade', Property::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $property = Property::find($request->id);

                if (!$property) {
                    throw new OperationException('Erro ao ler propriedade na operação de edição', Property::getTableName(), "Propriedade não encontrada: {$request->id}", 409);
                }
            } else {
                $property = new Property();
            }

            $property->name = $request->name;
            $property->admin_id = $request->admin_id;
            $property->state_subscription = $request->state_subscription ?? "";
            $property->cep = $request->cep ?? "";
            $property->street = $request->street ?? "";
            $property->neighborhood = $request->neighborhood ?? "";
            $property->number = $request->number ?? 0;
            $property->uf = $request->uf;
            $property->city = $request->city;
            $property->complement = $request->complement ?? "";
            $property->cnpj = $request->cnpj ?? "";
            $property->coordinates = $request->latitude && $request->longitude ? new Point($request->latitude, $request->longitude) : NULL;

            $property->save();

            $text = $request->id ? 'editada' : 'cadastrada';

            return response()->json([
                'status' => 200,
                'msg' => "Propriedade {$text} com sucesso",
                'property' => $property
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Altera o status de uma propriedade
     * @param admin_id
     * @param id - id da propriedade
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do propriedade', Property::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $property = Property::find($request->id);

            if (!$property) {
                throw new OperationException('Erro ao ler propriedade na operação de alteração de status', Property::getTableName(), "Propriedade não encontrada: {$request->id}", 409);
            }

            $property->status = 0;
            $property->save();

            return response()->json([
                'status' => 200,
                'msg' => "Propriedade removida com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Lê lavouras vinculadas e não vinculadas a uma propriedade
     * @param null $id
     * @param Request $request (filtros)
     * @return \Illuminate\Http\JsonResponse
     */
    public function readLinkedCrops($id = null, Request $request)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "ID Não enviado", 422);
            }

            $property = Property::find($id);
            $filter = $request->get("filter") ?? "";
            $linked_crops = collect([]);

            if ($property) {
                // lendo ultima safra
                $harvest = $request->get("harvest_id") ? Harvest::find($request->get("harvest_id")) : Harvest::where('status', 1)->where('is_last_harvest', 1)->first();


                // lendo lavouras vinculadas a propriedade que não estão na safra atual
                $available_crops = Crop::select('id', 'name', 'property_id', 'area')->where('property_id', $property->id)->where('status', 1)->orderBy('name', 'asc');

                // verificando se a propriedade já está ná ultima safra
                if ($property->crops->where('harvest_id', $harvest->id)->count() > 0) {

                    // se a propriedade já estiver na ultima safra, lendo lavouras vinculadas a propriedade que estão na safra atual
                    $linked_crops = $property->crops->where('status', 1)->where('harvest_id', $harvest->id)->map(function ($crop) {
                        $crop_item = $crop->crop->only('id', 'name', 'property_id', 'area');
                        $crop_item['is_subharvest'] = $crop->is_subharvest;
                        $crop_item['subharvest_name'] = $crop->subharvest_name;
                        $crop_item['used_area'] = $crop->data_seed->sum('area');
                        $crop_item['join_id'] = $crop->id;
                        return $crop_item;
                    })->values();

                    // lendo lavouras que não estão vinculadas na safra atual
                    $available_crops = $available_crops->whereNotIn('id', $linked_crops->pluck('id'));
                }


                if ($filter && $filter != 'null') {
                    $available_crops->where(function ($q) use ($filter) {
                        $q->where('name', 'like', "%{$filter}%")
                            ->orWhere('area', 'like', "%{$filter}%")
                            ->orWhere('city', 'like', "%{$filter}%");
                    });
                }

                $available_crops =  $available_crops->get();

                // used_area das available
                // $available_crops = $available_crops->map(function ($crop) use ($linked_crops) {
                //     $crop->used_area = $linked_crops->where('id', $crop->id)->sum('used_area');
                //     return $crop;
                // });

                // ordernando lavouras por nome
                $linked_crops = $linked_crops->sortBy('name')->values();
                $available_crops = $available_crops->sortBy('name')->values();

                return response()->json([
                    'status' => 200,
                    'linked_crops' => $linked_crops,
                    'available_crops' => $available_crops,
                ], 200);
            } else {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "Propriedade não encontrada: {$id}", 409);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Vincula lavouras a uma propriedade
     * @param Request $request
     * @param admin_id
     * @param property_id
     * @param crops - array de ids de lavouras
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkCrops(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_id' => 'required',
                'crops' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao vincular lavouras à propriedade', Property::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $property = Property::find($request->property_id);

            if (!$property) {
                throw new OperationException('Erro ao ler propriedade na operação de vincular lavouras', Property::getTableName(), "Propriedade não encontrada: {$request->id}", 409);
            }

            // lendo safra a ser adicionada
            $harvest = $request->harvest_id ? Harvest::find($request->harvest_id) : Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

            // vendo se a propriedade já está na ultima safra
            if ($property->crops->where('harvest_id', $harvest->id)->count() > 0) {
                $join_to_keep = [];

                // vinculando lavouras

                foreach ($request->crops as $crop_to_join) {
                    // adicionando id da lavoura às lavouras que não serão removidas
                    array_push($join_to_keep, $crop_to_join);

                    // se já não houver o vínculo, criamos ele
                    if (!$property->crops->where('harvest_id', $harvest->id)->where('crop_id', $crop_to_join)->first()) {
                        PropertyCropJoin::create([
                            'property_id' => $property->id,
                            'crop_id' => $crop_to_join,
                            'harvest_id' => $harvest->id,
                        ]);
                    }
                }

                // removendo vínculos que não estão no request
                $property->crops->where('harvest_id', $harvest->id)->whereNotIn('crop_id', $join_to_keep)->each(function ($join) {
                    $join->update(['status' => 0]);
                });
            } else {
                // vinculando lavouras
                foreach ($request->crops as $crop_to_join) {
                    PropertyCropJoin::create([
                        'property_id' => $property->id,
                        'crop_id' => $crop_to_join,
                        'harvest_id' => $harvest->id,
                    ]);
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Lavouras vinculadas com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Desvincula lavouras de uma propriedade
     * @param Request $request
     * @param admin_id
     * @param property_crop_join_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCropJoin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao desvincular lavoura da propriedade em um ano agrícola específico', PropertyCropJoin::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $join = PropertyCropJoin::find($request->property_crop_join_id);

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de desvincular lavoura  em um ano agrícola específico', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$request->property_crop_join_id}", 409);
            }

            $join->status = 0;
            $join->save();

            $joins_count = PropertyCropJoin::where(function ($query) use ($join) {
                $query->where('property_id', $join->property_id)
                    ->where('crop_id', $join->crop_id)
                    ->where('harvest_id', $join->harvest_id)
                    ->where('status', 1);
            })->count();

            if ($joins_count === 1) {
                $first_join = PropertyCropJoin::where(function ($query) use ($join) {
                    $query->where('property_id', $join->property_id)
                        ->where('crop_id', $join->crop_id)
                        ->where('harvest_id', $join->harvest_id)
                        ->where('status', 1);
                })->orderBy('id', 'asc')->first();

                if ($first_join) {
                    $first_join->update(['subharvest_name' => '']);
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Lavouras desvinculada com sucesso neste ano agrícola",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }


    /**
     * Criar subsafras
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function linkSubharvest(Request $request)
    {
        try {
            checkSection($request->admin_id);

            if ($request->subharvests) {
                foreach (json_decode($request->subharvests) as  $join) {
                    $crop_join = PropertyCropJoin::find($join->id);

                    $joins_count = PropertyCropJoin::where(function ($query) use ($crop_join) {
                        $query->where('property_id', $crop_join->property_id)
                            ->where('crop_id', $crop_join->crop_id)
                            ->where('harvest_id', $crop_join->harvest_id)
                            ->where('status', 1);
                    })->count();


                    $name = '';
                    if ($joins_count >= 1) {
                        $name = '(' . ($joins_count + 1) . ')';

                        if (($joins_count + 1) >= 2) {
                            $first_join = PropertyCropJoin::where(function ($query) use ($crop_join) {
                                $query->where('property_id', $crop_join->property_id)
                                    ->where('crop_id', $crop_join->crop_id)
                                    ->where('harvest_id', $crop_join->harvest_id)
                                    ->where('status', 1);
                            })->orderBy('id', 'asc')->first();

                            if ($first_join) {
                                $first_join->update(['subharvest_name' => '(1)']);
                            }
                        }
                    }

                    PropertyCropJoin::create([
                        'property_id' => $crop_join->property_id,
                        'crop_id' => $crop_join->crop_id,
                        'harvest_id' => $crop_join->harvest_id,
                        'is_subharvest' => 1,
                        'subharvest_name' => $name
                    ]);
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Subsafras criadas com sucesso neste ano agrícola",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }


    /**
     * Lê administradores vinculados a uma propriedade
     * @param null $id
     * @param Request $request (filtros)
     * @return \Illuminate\Http\JsonResponse
     */
    public function readLinkedAdmins($admin_id, $id)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "ID Não enviado", 422);
            }

            $property = Property::find($id);

            if ($property) {
                $linked_admins = $property->admins->map(function ($admin) {
                    return $admin->only('id', 'name');
                });

                $admins = Admin::select('id', 'name')->where('status', 1)->whereNotIn('id', $linked_admins->pluck('id'));

                // se o nivel do admin for diferente de 1, só verá os usuários abaixo dele
                if (Admin::find($admin_id)->access_level != 1) {
                    $admins = $admins->where('access_level', '>', Admin::find($admin_id)->access_level);
                }

                $admins = $admins->orderBy('name', 'asc')->get();

                return response()->json([
                    'status' => 200,
                    'linked_admins' => $linked_admins,
                    'admins' => $admins,
                ], 200);
            } else {
                throw new OperationException('Erro ao ler propriedade', Property::getTableName(), "Propriedade não encontrada: {$id}", 409);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }


    /**
     * Vincula administradores a uma propriedade
     * @param Request $request
     * @param admin_id
     * @param property_id
     * @param admins - array de ids de administradores
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkAdmins(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao vincular administradores à propriedade', Property::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $property = Property::find($request->property_id);

            if (!$property) {
                throw new OperationException('Erro ao ler propriedade na operação de vincular administradores', Property::getTableName(), "Propriedade não encontrada: {$request->id}", 409);
            }

            // vinculando administradores e removendo vinculos que nao vieram na lista da propriedade
            $property->admins()->sync($request->admins ?? []);


            return response()->json([
                'status' => 200,
                'msg' => "Administradores vinculados com sucesso",
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

    /**
     * Lê detalhes de uma lavoura vinculada a uma propriedade
     * @param null $property_crop_join_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function readPropertyHarvest($property_crop_join_id = null, Request $request)
    {
        try {

            if (!$property_crop_join_id) {
                throw new OperationException('Erro ao ler lavoura de umo ano agrícola na propriedade', PropertyCropJoin::getTableName(), "ID Não enviado", 422);
            }


            $join = PropertyCropJoin::find($property_crop_join_id);

            if ($request->admin_id) {
                $admin = Admin::find($request->admin_id);
                $admin_properties = $admin->all_properties();
                // verificando se a propriedade é do admin ou se está nos admins vinculados
                if ($admin->access_level != 1 && $join->property->admin_id != $admin->id && !in_array($join->property->id, $admin_properties->pluck('id')->toArray())) {
                    return response()->json([
                        'status' => 200,
                        'not_allowed' => true,
                    ], 200);
                }
            }

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de ler detalhes de uma lavoura vinculada a uma propriedade', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$property_crop_join_id}", 409);
            }

            if ($join->data_seed->first()) {
                $join->crop->color = $join->data_seed->sortByDesc('area')->first()->product->color;
            } else {
                $join->crop->color = null;
            }

            checkSection($join->property->admin_id);

            if ($join->data_harvest->count() > 0) {
                $join->is_harvested = 1;
                $join->save();
            }

            $crop = $join->crop;
            $crop->used_area = $join->data_seed->sum('area');

            unset($join->data_seed);

            return response()->json([
                'status' => 200,
                'property' => Property::readProperty($join->property_id, $join->harvest_id, null, $request->get("with_draw_area")),
                'join' => $join,
                'crop' => $crop,
                'harvest' => $join->harvest,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    function isDateInRange($date, $earliestYear = 1900)
    {
        return $date->year >= $earliestYear;
    }

    /**
     * Leitura das informações da aba "Informações de safra"
     * @param null $property_crop_join_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function readCropHarvestDetails($property_crop_join_id, Request $request)
    {
        try {

            if (!$property_crop_join_id) {
                throw new OperationException('Erro ao ler lavoura de um ano agrícola na propriedade', PropertyCropJoin::getTableName(), "ID Não enviado", 422);
            }

            $join = PropertyCropJoin::with(['rain_gauge', 'property', 'crop', 'harvest', 'stock_exits'])->find($property_crop_join_id);

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de ler detalhes de uma lavoura vinculada a uma propriedade', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$property_crop_join_id}", 409);
            }

            // lendo ultimo plantio

            $end_plant_rain_gauges = null;
            $end_disease = null;

            if ($join->data_harvest->sortBy('date')->first()) {
                $end_plant_rain_gauges = $join->data_harvest->sortBy('date')->first()->date;
                $end_disease = $join->data_harvest->sortBy('date')->first()->date;
            }

            $search_end_rain_gauge = false;

            $type = $request->get("type") && $request->get("type") != "null" ? $request->get("type") : "custom";

            if ($type == "custom") {
                if ($request->get("start_date_rain_gauge") != "" && $request->get("start_date_rain_gauge") != 'null' && $request->get("end_date_rain_gauge") != "" && $request->get("end_date_rain_gauge") != 'null') {
                    $last_plant_rain_gauges = $request->get("start_date_rain_gauge");
                    $end_plant_rain_gauges = $request->get("end_date_rain_gauge");
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', $last_plant_rain_gauges)->whereDate('date', '<=', $end_plant_rain_gauges)->get();
                } else {
                    $search_end_rain_gauge = true;

                    try {
                        $rainGaugeDate = $join->rain_gauge->sortBy('date')->first();
                        if ($rainGaugeDate) {
                            $date = Carbon::createFromFormat("Y-m-d", $rainGaugeDate->date);
                            // Verificação adicional para o range da data
                            if (!$this->isDateInRange($date)) {
                                throw new \Exception("Date is out of acceptable range.");
                            }
                            $last_plant_rain_gauges = $date->format('Y-m-d') != date('Y-m-d') ? $date : Carbon::now()->subDays(90);
                        } else {
                            throw new \Exception("No rain gauge data available.");
                        }
                    } catch (\Exception $e) {
                        // Fallback para data_seed ou subtrai 90 dias da data atual se a data de rain_gauge for inválida ou estiver fora do range
                        $dataSeedDate = $join->data_seed->sortBy('date')->first();
                        if ($dataSeedDate) {
                            try {
                                $last_plant_rain_gauges = Carbon::createFromFormat("Y-m-d", $dataSeedDate->date);
                                if (!$this->isDateInRange($last_plant_rain_gauges)) {
                                    // Se a data de data_seed também for inválida, usa a data atual subtraindo 90 dias
                                    $last_plant_rain_gauges = Carbon::now()->subDays(90);
                                }
                            } catch (\Exception $e) {
                                // Se a data de data_seed for inválida, usa a data atual subtraindo 90 dias
                                $last_plant_rain_gauges = Carbon::now()->subDays(90);
                            }
                        } else {
                            // Se não houver data_seed, usa a data atual subtraindo 90 dias
                            $last_plant_rain_gauges = Carbon::now()->subDays(90);
                        }
                    }

                    if ($last_plant_rain_gauges > Carbon::now()) {
                        $last_plant_rain_gauges = $join->rain_gauge->count() > 0 ? $join->rain_gauge->first()->date : Carbon::now()->subDays(30);
                    }

                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', $last_plant_rain_gauges);

                    if ($end_plant_rain_gauges) {
                        $rain_gauges = $rain_gauges->whereDate('date', '<=', $end_plant_rain_gauges);
                    }

                    $rain_gauges = $rain_gauges->get();
                }


                if ($request->get("start_date_disease") != "" && $request->get("start_date_disease") != 'null' && $request->get("end_date_disease") != "" && $request->get("end_date_disease") != 'null') {
                    $last_plant_disease = $request->get("start_date_disease");
                    $end_plant_disease = $request->get("end_date_disease");
                    $diseases = $join->diseases()->with('disease')->orderBy("id", 'asc')->whereDate('open_date', '>=', $last_plant_disease)->whereDate('open_date', '<=', $end_plant_disease)->get();
                } else {
                    if ($join->data_seed->sortBy('date')->first()) {
                        $last_plant_disease = $join->data_seed->sortBy('date')->first()->date;
                    } else {
                        $last_plant_disease = $join->diseases->last() && $join->diseases->last()->open_date != date('Y-m-d') ? (new \DateTime($join->diseases->last()->open_date))->format('Y-m-d') : Carbon::now()->subDays(30);
                    }

                    if ($last_plant_disease > Carbon::now()) {
                        $last_plant_disease = $join->diseases->count() > 0 ? $join->diseases->first()->open_date : Carbon::now()->subDays(30);
                    }

                    $diseases = $join->diseases()->with('disease')->orderBy("id", 'asc')->whereDate('open_date', '>=', $last_plant_disease);

                    if ($end_disease) {
                        $diseases = $diseases->whereDate('open_date', '<=', $end_disease);
                    }

                    $diseases = $diseases->get();
                }
            } else {
                $search_end_rain_gauge = true;

                switch ($type) {
                    case 'weekly':
                        $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(7));
                        $last_plant_rain_gauges = Carbon::now()->subDays(7);

                        $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(7));
                        $last_plant_disease = Carbon::now()->subDays(7);
                        break;
                    case 'monthly':
                        $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(30));
                        $last_plant_rain_gauges = Carbon::now()->subDays(30);

                        $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(30));
                        $last_plant_disease = Carbon::now()->subDays(30);
                        break;
                    case 'quarter':
                        $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(90));
                        $last_plant_rain_gauges = Carbon::now()->subDays(90);

                        $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(90));
                        $last_plant_disease = Carbon::now()->subDays(90);
                        break;
                    case 'semester':
                        $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(180));
                        $last_plant_rain_gauges = Carbon::now()->subDays(180);

                        $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(180));
                        $last_plant_disease = Carbon::now()->subDays(180);
                        break;
                    case 'anual':
                        $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(365));
                        $last_plant_rain_gauges = Carbon::now()->subDays(365);

                        $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(365));
                        $last_plant_disease = Carbon::now()->subDays(365);
                        break;
                }

                if ($end_plant_rain_gauges) {
                    $rain_gauges = $rain_gauges->whereDate('date', '<=', $end_plant_rain_gauges);
                }

                if ($end_disease) {
                    $diseases = $diseases->whereDate('open_date', '<=', $end_disease);
                }

                $rain_gauges = $rain_gauges->get();
                $diseases = $diseases->get();
            }

            // verificando se as datas de inicio nao estao a frente da data atual
            if ($last_plant_rain_gauges > Carbon::now()) {
                $last_plant_rain_gauges = Carbon::now()->subDays(30);
            }

            if ($last_plant_disease > Carbon::now()) {
                $last_plant_disease = Carbon::now()->subDays(30);
            }

            list($rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume, $diseases) = self::filterRainGaugesAndDiseases($type,  $rain_gauges, $diseases, $last_plant_rain_gauges, $last_plant_disease, $end_plant_rain_gauges);

            $total_costs = 0;
            $product_types_percentage = [
                "Semente" => 0,
                "Adjuvante" => 0,
                "Biológico" => 0,
                "Fertilizante foliar" => 0,
                "Fungicida" => 0,
                "Herbicida" => 0,
                "Inseticida" => 0,
                "Fertilizante" => 0,
                "Regulador de crescimento" => 0,
                "Indutor" => 0,
            ];

            $product_types_price = [
                "Semente" => 0,
                "Adjuvante" => 0,
                "Biológico" => 0,
                "Fertilizante foliar" => 0,
                "Fungicida" => 0,
                "Herbicida" => 0,
                "Inseticida" => 0,
                "Fertilizante" => 0,
                "Regulador de crescimento" => 0,
                "Indutor" => 0,
            ];

            $product_types_price_per_property = [
                "Semente" => 0,
                "Adjuvante" => 0,
                "Biológico" => 0,
                "Fertilizante foliar" => 0,
                "Fungicida" => 0,
                "Herbicida" => 0,
                "Inseticida" => 0,
                "Fertilizante" => 0,
                "Regulador de crescimento" => 0,
                "Indutor" => 0,
            ];

            $incomings = $join->property->stock_incomings()->with('stock')->whereHas('stock.product')->whereHas('stock.stock_exits', function ($q) use ($join) {
                $q->where('properties_crops_id', $join->id);
            })->get();

            // dd($incomings);

            $join->data_seed->each(function ($data) use (&$total_costs, &$product_types_price, $incomings, $join) {
                $total_dosage = $data->kilogram_per_ha * $join->crop->area;
                $total_original_dose = $total_dosage;

                $avg_price = 0;
                $avg_quantity = 0;

                $stock_product = $incomings->where('stock.product_id', $data->product->id)->where("stock.product_variant", $data->product_variant);

                $stock_product->each(function ($stock) use (&$avg_price, &$total_dosage, &$avg_quantity) {
                    if ($total_dosage > 0) {
                        if ($stock->quantity > $total_dosage) {
                            $total_to_use = $total_dosage;
                        } else {
                            $total_to_use = $stock->quantity - $total_dosage;

                            if ($total_to_use < 1) {
                                $total_to_use = $stock->quantity;
                            }
                        }

                        $avg_price += $stock->value * $total_to_use;
                        $avg_quantity += $total_to_use;

                        $total_dosage -= $total_to_use;
                    }
                });

                $total_unit_price = $avg_quantity > 0 ? $avg_price / $avg_quantity : 0;
                $total_unit_per_quantity = $total_unit_price * $total_original_dose;

                $total_costs += $total_unit_per_quantity;
                $product_types_price['Semente'] += $total_unit_per_quantity;
            });

            $join->data_input->each(function ($data) use (&$total_costs, &$product_types_price, $incomings, $join) {
                $total_dosage = $data->dosage * $join->crop->area;
                $total_original_dose = $total_dosage;

                $avg_price = 0;
                $avg_quantity = 0;

                $stock_product = $incomings->where('stock.product_id', $data->product->id);

                $stock_product->each(function ($stock) use (&$avg_price, &$total_dosage, &$avg_quantity) {
                    if ($total_dosage > 0) {
                        if ($stock->quantity > $total_dosage) {
                            $total_to_use = $total_dosage;
                        } else {
                            $total_to_use = $stock->quantity - $total_dosage;

                            if ($total_to_use < 1) {
                                $total_to_use = $stock->quantity;
                            }
                        }

                        $avg_price += $stock->value * $total_to_use;
                        $avg_quantity += $total_to_use;

                        $total_dosage -= $total_to_use;
                    }
                });

                $total_unit_price = $avg_quantity > 0 ? $avg_price / $avg_quantity : 0;
                $total_unit_per_quantity = $total_unit_price * $total_original_dose;

                $total_costs += $total_unit_per_quantity;


                switch ($data->product->type) {
                    case 2:
                        switch ($data->product->object_type) {
                            case 1:
                                $product_types_price['Adjuvante'] += $total_unit_per_quantity;
                                break;
                            case 2:
                                $product_types_price['Biológico'] += $total_unit_per_quantity;
                                break;
                            case 3:
                                $product_types_price['Fertilizante foliar'] += $total_unit_per_quantity;
                                break;
                            case 4:
                                $product_types_price['Fungicida'] += $total_unit_per_quantity;
                                break;
                            case 5:
                                $product_types_price['Herbicida'] += $total_unit_per_quantity;
                                break;
                            case 6:
                                $product_types_price['Inseticida'] += $total_unit_per_quantity;
                                break;
                            case 7:
                                $product_types_price['Regulador de crescimento'] += $total_unit_per_quantity;
                                break;
                            case 8:
                                $product_types_price['Indutor'] += $total_unit_per_quantity;
                                break;
                        }
                        break;
                    case 3:
                        $product_types_price['Fertilizante'] += $total_unit_per_quantity;
                        break;
                }
            });

            if ($total_costs > 0) {
                $product_types_percentage["Semente"] = $product_types_price['Semente'] > 0 ? number_format(($product_types_price['Semente'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Adjuvante"] = $product_types_price['Adjuvante'] > 0 ? number_format(($product_types_price['Adjuvante'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Biológico"] = $product_types_price['Biológico'] > 0 ? number_format(($product_types_price['Biológico'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Fertilizante foliar"] = $product_types_price['Fertilizante foliar'] > 0 ? number_format(($product_types_price['Fertilizante foliar'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Fungicida"] = $product_types_price['Fungicida'] > 0 ? number_format(($product_types_price['Fungicida'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Herbicida"] = $product_types_price['Herbicida'] > 0 ? number_format(($product_types_price['Herbicida'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Inseticida"] = $product_types_price['Inseticida'] > 0 ? number_format(($product_types_price['Inseticida'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Fertilizante"] = $product_types_price['Fertilizante'] > 0 ? number_format(($product_types_price['Fertilizante'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Regulador de crescimento"] = $product_types_price['Regulador de crescimento'] > 0 ? number_format(($product_types_price['Regulador de crescimento'] * 100) / $total_costs, 2, '.', '') : 0;
                $product_types_percentage["Indutor"] = $product_types_price['Indutor'] > 0 ? number_format(($product_types_price['Indutor'] * 100) / $total_costs, 2, '.', '') : 0;
            }

            $product_types_price_per_property['Semente'] = number_format($product_types_price['Semente'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Adjuvante'] = number_format($product_types_price['Adjuvante'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Biológico'] = number_format($product_types_price['Biológico'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Fertilizante foliar'] = number_format($product_types_price['Fertilizante foliar'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Fungicida'] = number_format($product_types_price['Fungicida'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Herbicida'] = number_format($product_types_price['Herbicida'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Inseticida'] = number_format($product_types_price['Inseticida'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Fertilizante'] = number_format($product_types_price['Fertilizante'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Regulador de crescimento'] = number_format($product_types_price['Regulador de crescimento'] / $join->crop->area, 2, ',', '.');
            $product_types_price_per_property['Indutor'] = number_format($product_types_price['Indutor'] / $join->crop->area, 2, ',', '.');

            $product_types_price['Semente'] = number_format($product_types_price['Semente'], 2, ',', '.');
            $product_types_price['Adjuvante'] = number_format($product_types_price['Adjuvante'], 2, ',', '.');
            $product_types_price['Biológico'] = number_format($product_types_price['Biológico'], 2, ',', '.');
            $product_types_price['Fertilizante foliar'] = number_format($product_types_price['Fertilizante foliar'], 2, ',', '.');
            $product_types_price['Fungicida'] = number_format($product_types_price['Fungicida'], 2, ',', '.');
            $product_types_price['Herbicida'] = number_format($product_types_price['Herbicida'], 2, ',', '.');
            $product_types_price['Inseticida'] = number_format($product_types_price['Inseticida'], 2, ',', '.');
            $product_types_price['Fertilizante'] = number_format($product_types_price['Fertilizante'], 2, ',', '.');
            $product_types_price['Regulador de crescimento'] = number_format($product_types_price['Regulador de crescimento'], 2, ',', '.');
            $product_types_price['Indutor'] = number_format($product_types_price['Indutor'], 2, ',', '.');

            $total_costs_per_property = number_format($total_costs / $join->crop->area, 2, ',', '.');

            // sort price and percentage desc
            arsort($product_types_percentage);
            arsort($product_types_price);


            unset($join->data_seed);
            unset($join->data_input);
            unset($join->rain_gauge);

            return response()->json([
                'status' => 200,
                'rain_gauge_infos' => $rain_gauge_infos,
                'rain_gauges' => $rain_gauges_graph,
                'rain_gauges_register' => $rain_gauges->sortByDesc("date")->values(),
                'rain_gauges_total_volume' => $rain_gauge_total_volume,
                'diseases' => $diseases,
                'last_plant_rain_gauges' => (new \DateTime($last_plant_rain_gauges))->format("Y-m-d"),
                'last_plant_disease' => (new \DateTime($last_plant_disease))->format("Y-m-d"),
                'end_plant_rain_gauges' => $end_plant_rain_gauges,
                'total_costs' => $total_costs,
                'product_types_percentage' => $product_types_percentage,
                'product_types_price_per_property' => $product_types_price_per_property,
                'product_types_price' => $product_types_price,
                'total_costs_per_property' => $total_costs_per_property,
                // 'seed_percentage' => $seed_percentage,
                // 'defensive_percentage' => $defensive_percentage,
                // 'fertilizer_percentage' => $fertilizer_percentage,
                'join' => $join
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function filterRainGauge($property_crop_join_id = null, $type = null, $begin = null, $end = null)
    {
        try {

            if (!$property_crop_join_id) {
                throw new OperationException('Erro ao ler lavoura de um ano agrícola na propriedade', PropertyCropJoin::getTableName(), "ID Não enviado", 422);
            }

            $join = PropertyCropJoin::with('rain_gauge')->find($property_crop_join_id);

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de ler detalhes de uma lavoura vinculada a uma propriedade', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$property_crop_join_id}", 409);
            }

            $end_plant_rain_gauges = null;

            if ($join->data_harvest->sortBy('date')->first()) {
                $end_plant_rain_gauges = $join->data_harvest->sortBy('date')->first()->date;
            }

            switch ($type) {
                case 'custom':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', $begin)->whereDate('date', '<=', $end);
                    $begin_rain_gauge = $begin;
                    break;
                case 'weekly':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(7));
                    $begin_rain_gauge = Carbon::now()->subDays(7);
                    break;
                case 'monthly':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(30));
                    $begin_rain_gauge = Carbon::now()->subDays(30);
                    break;
                case 'quarter':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(90));
                    $begin_rain_gauge = Carbon::now()->subDays(90);
                    break;
                case 'semester':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(180));
                    $begin_rain_gauge = Carbon::now()->subDays(180);
                    break;
                case 'anual':
                    $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', Carbon::now()->subDays(365));
                    $begin_rain_gauge = Carbon::now()->subDays(365);
                    break;
            }

            if ($end_plant_rain_gauges) {
                $rain_gauges = $rain_gauges->whereDate('date', '<=', $end_plant_rain_gauges);
            }

            $rain_gauges = $rain_gauges->get();

            list($rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume) = self::getRainGauges($type, $rain_gauges, $begin_rain_gauge, $end);

            return response()->json([
                'status' => 200,
                'rain_gauge_infos' => $rain_gauge_infos,
                'rain_gauges' => $rain_gauges_graph,
                'rain_gauges_register' => $rain_gauges,
                'rain_gauges_total_volume' => $rain_gauge_total_volume,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function filterDisease($property_crop_join_id = null, $type = null, $begin = null, $end = null)
    {
        try {

            if (!$property_crop_join_id) {
                throw new OperationException('Erro ao ler lavoura de um ano agrícola na propriedade', PropertyCropJoin::getTableName(), "ID Não enviado", 422);
            }

            $join = PropertyCropJoin::with('rain_gauge')->find($property_crop_join_id);

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de ler detalhes de uma lavoura vinculada a uma propriedade', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$property_crop_join_id}", 409);
            }

            $end_disease = null;

            if ($join->data_harvest->sortBy('date')->first()) {
                $end_disease = $join->data_harvest->sortBy('date')->first()->date;
            }

            switch ($type) {
                case 'custom':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', $begin)->whereDate('open_date', '<=', $end);
                    $begin_disease = $begin;
                    break;
                case 'weekly':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(7));
                    $begin_disease = Carbon::now()->subDays(7);
                    break;
                case 'monthly':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(30));
                    $begin_disease = Carbon::now()->subDays(30);
                    break;
                case 'quarter':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(90));
                    $begin_disease = Carbon::now()->subDays(90);
                    break;
                case 'semester':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(180));
                    $begin_disease = Carbon::now()->subDays(180);
                    break;
                case 'anual':
                    $diseases = $join->diseases()->whereDate('open_date', '>=', Carbon::now()->subDays(365));
                    $begin_disease = Carbon::now()->subDays(365);
                    break;
            }

            if ($end_disease) {
                $diseases = $diseases->whereDate('open_date', '<=', $end_disease);
            }

            $diseases = $diseases->get();

            $diseases = self::getDiseases($type, $diseases, $begin_disease);

            return response()->json([
                'status' => 200,
                'diseases' => $diseases,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function filterRainGaugesAndDiseases($type, $rain_gauges, $diseases, $begin_plant_rain_gauges, $begin_disease, $end_plant_rain_gauges)
    {

        list($rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume) = self::getRainGauges($type, $rain_gauges, $begin_plant_rain_gauges, $end_plant_rain_gauges);

        $diseases = self::getDiseases($type, $diseases, $begin_disease);

        return [$rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume, $diseases];
    }

    public static function getRainGauges($type, $rain_gauges, $begin_plant_rain_gauges, $end_plant_rain_gauges)
    {
        // dias sem chuva (lendo diferença da data atual até o ultimo registro pluviômetro)
        $last_rain_gauge = $rain_gauges->last() ? $rain_gauges->last()->date : 0;

        $begin_plant_rain_gauges = self::validateDate($begin_plant_rain_gauges);
        $end_plant_rain_gauges = self::validateDate($end_plant_rain_gauges);

        // dd($end_plant_rain_gauges);

        if ($end_plant_rain_gauges) {
            $now = $end_plant_rain_gauges;
        } else {
            if ($rain_gauges->first() && $rain_gauges->first()->property_crop->data_harvest->first()) {
                $now = Carbon::createFromFormat('Y-m-d', $rain_gauges->first()->property_crop->data_harvest->first()->date);
            } else {
                $now = Carbon::now();
            }
        }

        $now = $end_plant_rain_gauges ? Carbon::createFromFormat('Y-m-d', $end_plant_rain_gauges) : Carbon::now();
        $diff = (clone $now)->diffInDays($last_rain_gauge);
        $diff_total = (clone $now)->diffInDays($begin_plant_rain_gauges);


        $max_interval = 0;
        $next_date = null; // Armazena a data do próximo registro na iteração

        $rain_gauge_total_volume = collect([]);
        $rain_gauges_graph = collect([]);


        foreach ($rain_gauges->sortByDesc('date') as $rain_gauge) {
            if ($next_date) {
                $current_date = new Carbon($rain_gauge->date);
                $interval = $current_date->diffInDays($next_date, false);

                if ($interval > $max_interval) {
                    $max_interval = $interval;
                }
            }
            $next_date = new Carbon($rain_gauge->date);

            $date = $next_date->format('d/m/Y');

            if (!isset($rain_gauges_graph[$date])) {
                $rain_gauges_graph[$date] = collect([]);
                $rain_gauge_total_volume[$date] = collect([]);
            }
        }

        // Considera também o intervalo entre o primeiro registro (o mais recente) e a data atual
        // if ($rain_gauges->sortByDesc('date')->first()) {
        //     $next_date = new Carbon($rain_gauges->sortByDesc('date')->first()->date);
        //     $interval = $next_date->diffInDays($now, false);
        //     if ($interval > $max_interval) {
        //         $max_interval = $interval;
        //     }
        // }

        $rain_gauge_infos = [
            'total_volume' => number_format($rain_gauges->sum('volume'), 2, ',', '.'),
            'avg_volume' => $rain_gauges->avg('volume') ? number_format(($rain_gauges->sum('volume') / ($diff_total + 1)), 2, ',', '.') : 0,
            'rain_interval' => $max_interval >= 0 ? $max_interval : 0,
            'days_without_rain' => ($diff_total + 1) - $rain_gauges->count() >= 0 ? ($diff_total + 1) - $rain_gauges->count() : 0,
            // 'days_without_rain' => $diff,
            'days_with_rain' => $rain_gauges->count() >= 0 ? $rain_gauges->count() : 0,
        ];

        $previous_date = null;


        $difference = 0;
        $difference_group = 0;

        $from =  $begin_plant_rain_gauges;
        $difference = (clone $now)->diffInDays($from);

        // switch ($type) {
        //     case 'custom':
        //         if ($difference > 10) {
        //             $difference_group = $difference > 150 ? ceil($difference / 20) : ceil($difference / 10);
        //         } else {
        //             $difference_group = 1;
        //         }
        //         break;
        //     case 'weekly':
        //         $difference_group = 1;
        //         break;
        //     case 'monthly':
        //         $difference_group = 7;
        //         break;
        //     case 'quarter':
        //         $difference_group = 15;
        //         break;
        //     case 'semester':
        //         $difference_group = 30;
        //         break;
        //     case 'anual':
        //         $difference_group = 60;
        //         break;
        // }
        // // if ($difference <= 100) {
        // $i = $difference;

        for ($j = 0; $j < $difference; $j++) {
            $date = (clone $now)->subDays($j)->format('d/m/Y');

            if (!isset($rain_gauges_graph[$date])) {
                $rain_gauges_graph[$date] = collect([]);
                $rain_gauge_total_volume[$date] = collect([]);
            }
        }

        // while ($i >= 0) {
        //     $index = $i;

        //     $date = (clone $now)->subDays($i)->format('d/m/Y');

        //     if (!isset($rain_gauges_graph[$date])) {
        //         $rain_gauges_graph[$date] = collect([]);
        //     }
        //     if (!isset($rain_gauge_total_volume[$date])) {
        //         $rain_gauge_total_volume[$date] = collect([]);
        //     }

        //     $i = $i >= 0 ? $i - $difference_group : 0;
        // }
        // } else {
        //     $rain_gauges_graph[Carbon::createFromFormat("Y-m-d", $from)->format("d/m/Y")] = collect([]);
        //     $rain_gauge_total_volume[Carbon::createFromFormat("Y-m-d", $from)->format("d/m/Y")] = collect([]);
        // }


        $rain_gauges_graph = $rain_gauges_graph->sortBy(function ($value, $key) {
            return Carbon::createFromFormat('d/m/Y', $key);
        });

        $rain_gauge_total_volume = $rain_gauge_total_volume->sortBy(function ($value, $key) {
            return Carbon::createFromFormat('d/m/Y', $key);
        });

        foreach ($rain_gauges_graph as $gauge_id => $dateGroups) {
            $gauge_id_formated = Carbon::createFromFormat('d/m/Y', $gauge_id)->format('Y-m-d');
            $rain_gauge_total_volume[$gauge_id] = $rain_gauges->where('date', '<=', $gauge_id_formated)->sum('volume');
            $rain_gauges_graph[$gauge_id] = $rain_gauges->where('date', $gauge_id_formated)->sum('volume');
        }

        return [$rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume];
    }

    public static function getDiseases($type, $diseases, $begin_disease)
    {
        $diseases_names = $diseases->pluck('disease.name')->unique();
        // $diseases = $diseases
        //     ->groupBy([
        //         function ($item) {
        //             return (new \DateTime($item->open_date))->format('d/m/Y');
        //         },
        //         'disease.name'
        //     ]);

        $now = Carbon::now();
        $from =  $begin_disease;
        $difference = $now->diffInDays($from);

        // switch ($type) {
        //     case 'custom':
        //         if ($difference > 10) {
        //             $difference_group = $difference > 150 ? ceil($difference / 20) : ceil($difference / 10);
        //         } else {
        //             $difference_group = 1;
        //         }
        //         break;
        //     case 'weekly':
        //         $difference_group = 1;
        //         break;
        //     case 'monthly':
        //         $difference_group = 7;
        //         break;
        //     case 'quarter':
        //         $difference_group = 15;
        //         break;
        //     case 'semester':
        //         $difference_group = 30;
        //         break;
        //     case 'anual':
        //         $difference_group = 60;
        //         break;
        // }

        // $i = $difference;

        $diseases_group = collect([]);

        $diseases->each(function ($disease) use ($diseases_group, $diseases_names) {
            $date = (new \DateTime($disease->open_date))->format('d/m/Y');

            if (!isset($diseases_group[$date])) {
                $diseases_group[$date] = collect([]);

                $diseases_names->each(function ($disease_name) use ($date, $diseases_group) {
                    $diseases_group[$date][$disease_name] = 0;
                });
            }
        });

        // while ($i >= 0) {
        //     $date = Carbon::now()->subDays($i)->format('d/m/Y');
        //     if (!isset($diseases_group[$date])) {
        //         $diseases_group[$date] = collect([]);
        //         $diseases_names->each(function ($disease_name) use ($date, $diseases_group) {
        //             $diseases_group[$date][$disease_name] = 0;
        //         });
        //     }

        //     $i = $i >= 0 ? $i - $difference_group : 0;
        // }


        // for ($i = $difference; $i >= 0; $i - $difference_group) {
        //     $i <= 0 ? $date = Carbon::now()->format('d/m/Y') : $date = Carbon::now()->subDays($i)->format('d/m/Y');
        //     $date = Carbon::now()->subDays($i)->format('d/m/Y');

        //     if (!isset($diseases[$date])) {
        //         $diseases[$date] = collect([]);
        //         $diseases_names->each(function ($disease_name) use ($date, $diseases) {
        //             $diseases[$date][$disease_name] = 0;
        //         });
        //     }
        // }

        $diseases_group = $diseases_group->sortBy(function ($value, $key) {
            return Carbon::createFromFormat('d/m/Y', $key);
        });

        $previous_date = null;

        foreach ($diseases_group as $date => $diseasesNames) {
            $date_formated = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');

            foreach ($diseasesNames as $name => $items) {

                if (!$diseases->where('open_date', $date_formated)->where('disease.name', $name)->first() && $diseases->where('open_date', $date_formated)->where('disease.name', '!=', $name)) {
                    $diseases_group[$date][$name] = 0;
                } else {
                    $diseases_group[$date][$name] = $diseases->where('open_date', '<=', $date_formated)->where('disease.name', $name)->first() ? $diseases->where('open_date', '<=', $date_formated)->where('disease.name', $name)->sortByDesc('open_date')->first()->incidency : 0;
                }
            }
            $previous_date = $date_formated;
        }

        // caso só tenha uma data de doença, adicionamos um dia anterior para gerar um gráfico
        if ($diseases_group->count() == 1) {
            $diseases_group->prepend(collect([]), Carbon::createFromFormat('d/m/Y', $diseases_group->keys()->first())->subDays(1)->format('d/m/Y'));

            $diseases_names->each(function ($disease_name) use ($diseases_group) {
                $diseases_group->first()->put($disease_name, 0);
            });
        }

        return $diseases_group;
    }

    /**
     * Lê o histórico das safras de uma propriedade
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    function readHarvestHistory($property_id)
    {
        try {

            if (!$property_id) {
                throw new OperationException('Erro ao ler histórico de anos agrícolas da propriedade', Property::getTableName(), "ID Não enviado", 422);
            }

            $property = Property::with('crops.crop')->find($property_id);

            if (!$property) {
                throw new OperationException('Erro ao ler propriedade na operação de ler histórico de anos agrícolas', Property::getTableName(), "Propriedade não encontrada: {$property_id}", 409);
            }

            // ler ultima safra
            // $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

            $harvests = Harvest::where('status', 1)->orderBy('name', 'desc')->get();

            foreach ($harvests as $harvest) {
                $harvest->crops_join = $property->crops()->with('crop')->where('harvest_id', $harvest->id)->get();
            }

            return response()->json([
                'status' => 200,
                'harvests' => $harvests,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function readCropsByOptions(Request $request)
    {
        try {

            $property_id = $request->get("property_id") ?? "";
            $harvest_id = $request->get("harvest_id") ?? "";

            // ler lavouras
            $joins = PropertyCropJoin::select('properties_crops_join.*',)->whereHas('crop')->with(['crop' => function ($q) {
                $q->select("id", "name", "area");
            }])->join('crops', 'crops.id', '=', 'properties_crops_join.crop_id')
                ->where('crops.status', 1)
                ->where('properties_crops_join.property_id', $property_id)
                ->where('harvest_id', $harvest_id)
                ->where('properties_crops_join.status', 1)
                ->orderBy('crops.name', 'asc')
                ->get();

            return response()->json([
                'status' => 200,
                'joins' => $joins
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function readCropHarvestDetailsMobile($property_crop_join_id, Request $request)
    {
        try {

            if (!$property_crop_join_id) {
                throw new OperationException('Erro ao ler lavoura de um ano agrícola na propriedade', PropertyCropJoin::getTableName(), "ID Não enviado", 422);
            }

            $join = PropertyCropJoin::with(['rain_gauge', 'property', 'crop', 'harvest'])->find($property_crop_join_id);

            if (!$join) {
                throw new OperationException('Erro ao ler vínculo na operação de ler detalhes de uma lavoura vinculada a uma propriedade', PropertyCropJoin::getTableName(), "Vínculo não encontrado: {$property_crop_join_id}", 409);
            }

            // lendo ultimo plantio

            $end_plant_rain_gauges = null;

            if ($join->data_harvest->sortBy('date')->first()) {
                $end_plant_rain_gauges = $join->data_harvest->sortBy('date')->first()->date;
            }

            try {
                $rainGaugeDate = $join->rain_gauge->sortBy('date')->first();
                if ($rainGaugeDate) {
                    $date = Carbon::createFromFormat("Y-m-d", $rainGaugeDate->date);
                    // Verificação adicional para o range da data
                    if (!$this->isDateInRange($date)) {
                        throw new \Exception("Date is out of acceptable range.");
                    }
                    $last_plant_rain_gauges = $date->format('Y-m-d') != date('Y-m-d') ? $date : Carbon::now()->subDays(90);
                } else {
                    throw new \Exception("No rain gauge data available.");
                }
            } catch (\Exception $e) {
                // Fallback para data_seed ou subtrai 90 dias da data atual se a data de rain_gauge for inválida ou estiver fora do range
                $dataSeedDate = $join->data_seed->sortBy('date')->first();
                if ($dataSeedDate) {
                    try {
                        $last_plant_rain_gauges = Carbon::createFromFormat("Y-m-d", $dataSeedDate->date);
                        if (!$this->isDateInRange($last_plant_rain_gauges)) {
                            // Se a data de data_seed também for inválida, usa a data atual subtraindo 90 dias
                            $last_plant_rain_gauges = Carbon::now()->subDays(90);
                        }
                    } catch (\Exception $e) {
                        // Se a data de data_seed for inválida, usa a data atual subtraindo 90 dias
                        $last_plant_rain_gauges = Carbon::now()->subDays(90);
                    }
                } else {
                    // Se não houver data_seed, usa a data atual subtraindo 90 dias
                    $last_plant_rain_gauges = Carbon::now()->subDays(90);
                }
            }

            if ($join->data_seed->sortBy('date')->first()) {
                $last_plant_disease = $join->data_seed->sortBy('date')->first()->date;
            } else {
                $last_plant_disease = $join->diseases->last() && $join->diseases->last()->open_date != date('Y-m-d') ? (new \DateTime($join->diseases->last()->open_date))->format('Y-m-d') : Carbon::now()->subDays(7);
            }

            // verificando se as datas de inicio nao estao a frente da data atual
            if ($last_plant_rain_gauges > Carbon::now()) {
                // menos 7 dias
                $last_plant_rain_gauges = Carbon::now()->subDays(7);
            }

            if ($last_plant_disease > Carbon::now()) {
                $last_plant_disease = Carbon::now()->subDays(7);
            }

            return response()->json([
                'status' => 200,
                'last_plant_rain_gauges' => (new \DateTime($last_plant_rain_gauges))->format("Y-m-d"),
                'last_plant_disease' => (new \DateTime($last_plant_disease))->format("Y-m-d"),
                'end_plant_rain_gauges' => $end_plant_rain_gauges,
                'join' => $join
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    // validando ano, mes e dia de uma data YYYY-mm-dd
    public static function validateDate($date_arg)
    {
        $date = explode("-", $date_arg);
        $now = Carbon::now()->format('Y-m-d');

        if (count($date) != 3) {
            return $now;
        }

        // ano invalido
        if ($date[0] < 1962 || $date[0] > (intval(date('Y')) + 5)) {
            return $now;
        }

        // mes invalido
        if ($date[1] < 1 || $date[1] > 12) {
            return $now;
        }

        // dia invalido (considerando 31 dias para todos os meses, exceto fevereiro)
        if (($date[2] < 1 || $date[2] > 31) || ($date[1] == 2 && $date[2] > 29)) {
            return $now;
        }

        return $date_arg;
    }
}
