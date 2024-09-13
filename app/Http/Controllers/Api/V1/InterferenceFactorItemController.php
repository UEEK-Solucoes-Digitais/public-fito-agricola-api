<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\DiseaseCultureJoin;
use App\Http\Controllers\Controller;
use App\Exceptions\OperationException;
use App\Models\InterferenceFactorItem;
use App\Models\PestCultureJoin;
use App\Models\PropertyManagementDataSeed;
use Illuminate\Support\Facades\Validator;

class InterferenceFactorItemController extends Controller
{
    public function list($type = null, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            list($interference_factors_items, $total) = InterferenceFactorItem::readInterferenceFactorItems($type, $filter, $page);

            return response()->json([
                'status' => 200,
                'interference_factors_items' => $interference_factors_items,
                'total' => $total
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

    public function listByJoin($crop_join_id = null)
    {
        try {

            // primeiro lemos todos os data_seeds do join
            $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $crop_join_id)->where('status', 1)->get();

            // pegamos as culturas dos data_seeds
            $cultures = $data_seeds->pluck('product_id')->toArray();

            // removendo culturas iguais
            $cultures = array_unique($cultures);

            // lendo pragas e doenças que possuem ligação com as culturas
            $diseases = InterferenceFactorItem::where('status', 1)->whereHas('cultures', function ($q) use ($cultures) {
                $q->whereIn('diseases_cultures_join.product_id', $cultures);
            })->get();

            $diseases = $diseases->sortBy('name')->values();

            $pests = InterferenceFactorItem::where('status', 1)->whereHas('cultures_pests', function ($q) use ($cultures) {
                $q->whereIn('pests_cultures_join.product_id', $cultures);
            })->get();

            $pests = $pests->sortBy('name')->values();

            if ($diseases->isEmpty()) {
                $diseases =  InterferenceFactorItem::where('type', 2)->where('status', 1)->get();
                $diseases = $diseases->sortBy('name')->values();
            }

            if ($pests->isEmpty()) {
                $pests =  InterferenceFactorItem::where('type', 3)->where('status', 1)->get();
                $pests = $pests->sortBy('name')->values();
            }

            return response()->json([
                'status' => 200,
                'diseases' => $diseases,
                'pests' => $pests,
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

    public function read($id = null)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler fator de interferência', InterferenceFactorItem::getTableName(), "ID Não enviado", 422);
            }

            $interference_factor_item = InterferenceFactorItem::readInterferenceFactorItem($id);

            if ($interference_factor_item) {
                return response()->json([
                    'status' => 200,
                    'interference_factor_item' => $interference_factor_item
                ], 200);
            } else {
                throw new OperationException('Erro ao ler fator de interferência', InterferenceFactorItem::getTableName(), "Fator de interferência não encontrado: {$id}", 409);
            }
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
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'name'  => 'required',
                'type'  => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar fator de interferência', InterferenceFactorItem::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $interference_factor_item = InterferenceFactorItem::find($request->id);

                if (!$interference_factor_item) {
                    throw new OperationException('Erro ao ler fator de interferência na operação de edição', InterferenceFactorItem::getTableName(), "Fator de interferência não encontrado: {$request->id}", 409);
                }
            } else {
                $interference_factor_item = new InterferenceFactorItem();
            }

            $interference_factor_item->name = $request->name;
            $interference_factor_item->scientific_name = $request->scientific_name ?? "";
            $interference_factor_item->type = $request->type;
            $interference_factor_item->observation = $request->observation ?? "";
            $interference_factor_item->save();

            if ($request->cultures) {
                if ($interference_factor_item->type  == 2) {
                    DiseaseCultureJoin::where('disease_id', $interference_factor_item->id)->delete();

                    foreach ($request->cultures as $culture) {
                        $join = new DiseaseCultureJoin();
                        $join->disease_id = $interference_factor_item->id;
                        $join->product_id = $culture;
                        $join->save();
                    }
                } else if ($interference_factor_item->type == 3) {
                    PestCultureJoin::where('pest_id', $interference_factor_item->id)->delete();

                    foreach ($request->cultures as $culture) {
                        $join = new PestCultureJoin();
                        $join->pest_id = $interference_factor_item->id;
                        $join->product_id = $culture;
                        $join->save();
                    }
                }
            }


            $text = $request->id ? 'editada' : 'cadastrada';

            return response()->json([
                'status' => 200,
                'msg' => InterferenceFactorItem::getRole($request->type) . " {$text} com sucesso",
                'interference_factor_item' => $interference_factor_item
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function delete(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do fator de interferência', InterferenceFactorItem::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $interference_factor_item = InterferenceFactorItem::find($request->id);

            if (!$interference_factor_item) {
                throw new OperationException('Erro ao ler fator de interferência na operação de alteração de status', InterferenceFactorItem::getTableName(), "Fator de interferência não encontrado: {$request->id}", 409);
            }

            $interference_factor_item->status = 0;
            $interference_factor_item->save();

            return response()->json([
                'status' => 200,
                'msg' => InterferenceFactorItem::getRole($interference_factor_item->type) . " removida com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
