<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($products, $total) = Product::readProducts($admin_id, $filter, $page);
            list($properties, $total) = Property::readProperties($admin_id, $filter);

            return response()->json([
                'status' => 200,
                'total' => $total,
                'products' => $products,
                'properties' => $properties,
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
                throw new OperationException('Erro ao ler produto', Product::getTableName(), "ID Não enviado", 422);
            }

            $product = Product::readProduct($id);

            if ($product) {
                return response()->json([
                    'status' => 200,
                    'product' => $product
                ], 200);
            } else {
                throw new OperationException('Erro ao ler produto', Product::getTableName(), "Produto não encontrada: {$id}", 409);
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
                'type' => 'required|min:1',
                'object' => 'required|min:1',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar produto', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $product = Product::find($request->id);

                if (!$product) {
                    throw new OperationException('Erro ao ler produto na operação de edição', Product::getTableName(), "Produto não encontrada: {$request->id}", 409);
                }
            } else {
                $product = new Product();
            }

            $product->type = $request->type;
            $product->item_id = $request->object;
            $product->name = $request->name;

            if (!$request->id) {
                $admin = Admin::find($request->admin_id);
                $product->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
                $product->status = $admin->access_level == 1 ? 1 : 2;
            }

            $product->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Produto {$text} com sucesso",
                'product' => $product
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
                throw new OperationException('Erro ao alterar status do produto', Product::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $product = Product::find($request->id);

            if (!$product) {
                throw new OperationException('Erro ao ler produto na operação de alteração de status', Product::getTableName(), "Produto não encontrada: {$request->id}", 409);
            }

            $product->status = $request->status;
            $product->save();

            return response()->json([
                'status' => 200,
                'msg' => "Produto " . getTextStatus($request->status, 0, 2) . " com sucesso",
                'product' => $product
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
