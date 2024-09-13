<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";


            list($banks, $total) = Bank::readBanks($filter, $page);

            return response()->json([
                'status' => 200,
                'banks' => $banks,
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
                throw new OperationException('Erro ao ler Banco', Bank::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');

            $bank = Bank::readBank($id, $admin_id);

            if ($bank) {

                return response()->json([
                    'status' => 200,
                    'bank' => $bank
                ], 200);
            } else {
                throw new OperationException('Erro ao ler Banco', Bank::getTableName(), "Banco não encontrado: {$id}", 409);
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
                'image' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar conta', Bank::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $bank = Bank::readBank($request->id);

                if (!$bank) {
                    throw new OperationException('Erro ao ler conta na operação de edição', Bank::getTableName(), "Conta não encontrado: {$request->id}", 409);
                }
            } else {
                $bank = new Bank();
            }

            $bank->name = $request->name;
            $bank->image = $request->image;
            $bank->save();


            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Banco {$text} com sucesso",
                'bank' => $bank,
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
                throw new OperationException('Erro ao alterar status do banco', Bank::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $bank = Bank::readBank($request->id);

            if (!$bank) {
                throw new OperationException('Erro ao ler Banco na operação de alteração de status', Bank::getTableName(), "Banco não encontrado: {$request->id}", 409);
            }

            $bank->status = 0;
            $bank->save();

            return response()->json([
                'status' => 200,
                'msg' => "Banco removido com sucesso",
                'bank' => $bank
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
