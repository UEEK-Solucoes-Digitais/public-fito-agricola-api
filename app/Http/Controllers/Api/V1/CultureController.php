<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Culture;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CultureController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            checkSection($admin_id);
            // dd($admin_id);
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            // insumos são produtos, portanto a leitura mudou
            // list($cultures, $total) = Culture::readCultures($filter, $page);

            // $culture_saw = [];

            // foreach ($cultures as $culture) {
            //     if (!in_array($culture->name, $culture_saw)) {
            //         $cultures_code = $cultures->where("name", $culture->name)->pluck("code")->toArray();

            //         if (!Product::where("name", $culture->name)->exists()) {
            //             Product::create([
            //                 "admin_id" => $admin_id,
            //                 "name" => $culture->name,
            //                 "extra_column" => implode(",", $cultures_code),
            //                 "type" => 1,
            //                 "status" => 1,
            //             ]);
            //         }
            //         $culture_saw[] = $culture->name;
            //     }
            // }

            list($cultures, $total) = Product::readProducts($admin_id, $filter, $page, 1);

            return response()->json([
                'status' => 200,
                'cultures' => $cultures,
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
                throw new OperationException('Erro ao ler cultura', Product::getTableName(), "ID Não enviado", 422);
            }

            $culture = Product::readProduct($id);

            if ($culture) {
                return response()->json([
                    'status' => 200,
                    'culture' => $culture
                ], 200);
            } else {
                throw new OperationException('Erro ao ler cultura', Product::getTableName(), "Cultura não encontrada: {$id}", 409);
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
                throw new OperationException('Erro ao cadastrar/editar cultura', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $culture = Product::find($request->id);

                if (!$culture) {
                    throw new OperationException('Erro ao ler cultura na operação de edição', Product::getTableName(), "Cultura não encontrada: {$request->id}", 409);
                }
            } else {
                $culture = new Product();
            }

            $culture->name = mb_strtoupper($request->name, 'UTF-8');

            $culture->type = 1;
            $array_sort = $request->code;
            sort($array_sort);
            $culture->extra_column = join(',', $array_sort);

            if (!$request->id) {
                // random color
                $culture->color = "#" . substr(md5($request->name), 0, 6);

                $admin = Admin::find($request->admin_id);
                $culture->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
                $culture->status = !$request->status ? ($admin->access_level == 1 ? 1 : 2) : $request->status;
            }

            $culture->save();

            $text = $request->id ? 'editada' : 'cadastrada';

            return response()->json([
                'status' => 200,
                'msg' => "Cultura {$text} com sucesso",
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

    public function alterStatus(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da cultura', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $culture = Product::find($request->id);

            if (!$culture) {
                throw new OperationException('Erro ao ler cultura na operação de alteração de status', Product::getTableName(), "Cultura não encontrada: {$request->id}", 409);
            }

            $culture->status = $request->status;
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
