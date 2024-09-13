<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Fertilizer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FertilizerController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            checkSection($admin_id);

            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            // list($fertilizers, $total) = Product::readFertilizers($filter, $page);

            // $fertilizer_saw = [];

            // foreach ($fertilizers as $fertilizer) {
            //     Product::create([
            //         "admin_id" => $admin_id,
            //         "name" => $fertilizer->name,
            //         "extra_column" => $fertilizer->observation,
            //         "type" => 3,
            //         "status" => 1,
            //     ]);
            // }
            list($fertilizers, $total) = Product::readProducts($admin_id, $filter, $page, 3);

            return response()->json([
                'status' => 200,
                'fertilizers' => $fertilizers,
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

    public function read($id = null)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler fertilizante', Product::getTableName(), "ID Não enviado", 422);
            }

            $fertilizer = Product::readFertilizer($id);

            if ($fertilizer) {
                return response()->json([
                    'status' => 200,
                    'fertilizer' => $fertilizer
                ], 200);
            } else {
                throw new OperationException('Erro ao ler fertilizante', Product::getTableName(), "Fertilizante não encontrado: {$id}", 409);
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
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar fertilizante', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $fertilizer = Product::find($request->id);

                if (!$fertilizer) {
                    throw new OperationException('Erro ao ler fertilizante na operação de edição', Product::getTableName(), "Fertilizante não encontrado: {$request->id}", 409);
                }
            } else {
                $fertilizer = new Product();
            }

            $fertilizer->name = mb_strtoupper($request->name, 'UTF-8');
            $fertilizer->extra_column = $request->observation ?? "";
            $fertilizer->type = 3;
            $fertilizer->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Fertilizante {$text} com sucesso",
                'fertilizer' => $fertilizer
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
                throw new OperationException('Erro ao alterar status do fertilizante', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $fertilizer = Product::find($request->id);

            if (!$fertilizer) {
                throw new OperationException('Erro ao ler fertilizante na operação de alteração de status', Product::getTableName(), "Fertilizante não encontrado: {$request->id}", 409);
            }

            $fertilizer->status = 0;
            $fertilizer->save();

            return response()->json([
                'status' => 200,
                'msg' => "Fertilizante removido com sucesso",
                'fertilizer' => $fertilizer
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
