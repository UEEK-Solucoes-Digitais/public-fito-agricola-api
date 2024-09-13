<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Defensive;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DefensiveController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            checkSection($admin_id);
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            // list($defensives, $total) = Defensive::readDefensives($filter, $page);

            // $defensive_saw = [];

            // foreach ($defensives as $defensive) {
            //     Product::create([
            //         "admin_id" => $admin_id,
            //         "name" => $defensive->name,
            //         "extra_column" => $defensive->observation,
            //         "object_type" => $defensive->type,
            //         "type" => 2,
            //         "status" => 1,
            //     ]);
            // }
            list($defensives, $total) = Product::readProducts($admin_id, $filter, $page, 2);


            return response()->json([
                'status' => 200,
                'defensives' => $defensives,
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
                throw new OperationException('Erro ao ler defensivo', Product::getTableName(), "ID Não enviado", 422);
            }

            $defensive = Product::readProduct($id);

            if ($defensive) {
                return response()->json([
                    'status' => 200,
                    'defensive' => $defensive
                ], 200);
            } else {
                throw new OperationException('Erro ao ler defensivo', Product::getTableName(), "Defensivo não encontrado: {$id}", 409);
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
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar defensivo', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $defensive = Product::find($request->id);

                if (!$defensive) {
                    throw new OperationException('Erro ao ler defensivo na operação de edição', Product::getTableName(), "Defensivo não encontrado: {$request->id}", 409);
                }
            } else {
                $defensive = new Product();
            }

            $defensive->name = mb_strtoupper($request->name, 'UTF-8');
            $defensive->type = 2;
            $defensive->object_type = $request->type;
            $defensive->extra_column = $request->observation ?? "";
            $defensive->is_for_seeds = $request->isForSeed ?? 0;

            if (!$request->id) {
                $admin = Admin::find($request->admin_id);
                $defensive->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
                $defensive->status = !$request->status ? ($admin->access_level == 1 ? 1 : 2) : $request->status;
            }

            $defensive->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Defensivo {$text} com sucesso",
                'defensive' => $defensive
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
                throw new OperationException('Erro ao alterar status do defensivo', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $defensive = Product::find($request->id);

            if (!$defensive) {
                throw new OperationException('Erro ao ler defensivo na operação de alteração de status', Product::getTableName(), "Defensivo não encontrado: {$request->id}", 409);
            }

            $defensive->status = 0;
            $defensive->save();

            return response()->json([
                'status' => 200,
                'msg' => "Defensivo removido com sucesso",
                'defensive' => $defensive
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function alterType(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da defensivo', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $culture = Product::find($request->id);

            if (!$culture) {
                throw new OperationException('Erro ao ler defensivo na operação de alteração de status', Product::getTableName(), "Cultura não encontrada: {$request->id}", 409);
            }

            $culture->object_type = $request->type;
            $culture->save();

            return response()->json([
                'status' => 200,
                'msg' => "Cultura " . getTextStatus($request->status, 1) . " com sucesso",
                'culture' => $culture
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
