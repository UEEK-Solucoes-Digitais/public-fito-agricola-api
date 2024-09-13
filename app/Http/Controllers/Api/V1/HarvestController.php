<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Harvest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\OperationException;
use App\Models\PropertyCropJoin;

class HarvestController extends Controller
{
    public function list(Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($harvests, $total) = Harvest::readHarvests($filter, $page);

            return response()->json([
                'status' => 200,
                'harvests' => $harvests,
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

    public function read($id = null)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler ano agrícola', Harvest::getTableName(), "ID Não enviado", 422);
            }

            $harvest = Harvest::readHarvest($id);

            if ($harvest) {
                return response()->json([
                    'status' => 200,
                    'harvest' => $harvest
                ], 200);
            } else {
                throw new OperationException('Erro ao ler ano agrícola', Harvest::getTableName(), "Ano agrícola não encontrada: {$id}", 409);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function last()
    {
        try {
            $harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

            if ($harvest) {
                return response()->json([
                    'status' => 200,
                    'harvest' => $harvest
                ], 200);
            } else {
                throw new OperationException('Erro ao ler ano agrícola', Harvest::getTableName(), "Nenhum ano agrícola encontrada", 409);
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
                'name' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar ano agrícola', Harvest::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $harvest = Harvest::find($request->id);

                if (!$harvest) {
                    throw new OperationException('Erro ao ler ano agrícola na operação de edição', Harvest::getTableName(), "Ano agrícola não encontrada: {$request->id}", 409);
                }
            } else {
                $harvest = new Harvest();
            }

            // lendo ultima safra cadastrada sem ser a que acabou de cadastrar
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

            $harvest->name = $request->name;
            $harvest->start_date = $request->start_date;
            $harvest->end_date = $request->end_date;
            $harvest->is_last_harvest = $request->is_last_harvest ?? 0;
            $harvest->save();

            if ($request->is_last_harvest) {
                Harvest::where('status', 1)->where('is_last_harvest', 1)->where('id', '!=', $harvest->id)->update(['is_last_harvest' => 0]);
            }

            $text = $request->id ? 'editado' : 'cadastrado';

            // se for criada uma nova safra, todas as lavouras são vinculadas novamente à essa nova safra
            // if (!$request->id) {

            //     $crops_join = PropertyCropJoin::where("status", 1)->where("harvest_id", $last_harvest->id)->get();

            //     foreach ($crops_join as $crop_join) {
            //         $new_crop_join = new PropertyCropJoin();
            //         $new_crop_join->property_id = $crop_join->property_id;
            //         $new_crop_join->crop_id = $crop_join->crop_id;
            //         $new_crop_join->harvest_id = $harvest->id;
            //         $new_crop_join->save();
            //     }
            // }

            return response()->json([
                'status' => 200,
                'msg' => "Ano agrícola {$text} com sucesso",
                'harvest' => $harvest
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
                throw new OperationException('Erro ao alterar status do ano agrícola', Harvest::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $harvest = Harvest::find($request->id);

            if (!$harvest) {
                throw new OperationException('Erro ao ler ano agrícola na operação de alteração de status', Harvest::getTableName(), "Fertilizante não encontrado: {$request->id}", 409);
            }

            $harvest->status = 0;
            $harvest->save();

            return response()->json([
                'status' => 200,
                'msg' => "Fertilizante removido com sucesso",
                'harvest' => $harvest
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
