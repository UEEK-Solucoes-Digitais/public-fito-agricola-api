<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccountManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankAccountManagementController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($accounts, $total) = BankAccountManagement::readAccounts($admin_id, $filter, $page);
            list($banks, $_) = Bank::readBanks(null, null);

            return response()->json([
                'status' => 200,
                'accounts' => $accounts,
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
                throw new OperationException('Erro ao ler conta', BankAccountManagement::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');

            $account = BankAccountManagement::readAccount($id, $admin_id);

            if ($account) {
                list($banks, $total) = Bank::readBanks(null, null);

                return response()->json([
                    'status' => 200,
                    'banks' => $banks,
                    'account' => $account
                ], 200);
            } else {
                throw new OperationException('Erro ao ler conta', BankAccountManagement::getTableName(), "Conta não encontrada: {$id}", 409);
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
                'bank_id' => 'required',
                'agency' => 'required',
                'account' => 'required',
                'start_balance' => 'required',
                'start_date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar conta', BankAccountManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $account = BankAccountManagement::readAccount($request->id);

                if (!$account) {
                    throw new OperationException('Erro ao ler conta na operação de edição', BankAccountManagement::getTableName(), "Conta não encontrada: {$request->id}", 409);
                }
            } else {
                $account = new BankAccountManagement();
            }

            $account->admin_id = $request->admin_id;
            $account->bank_id = $request->bank_id;
            $account->agency = $request->agency;
            $account->account = $request->account;
            $account->type = $request->type;
            $account->start_balance = isString($request->start_balance);
            $account->start_date = $request->start_date;
            $account->status = $request->status ?? 1;

            $account->save();


            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Conta {$text} com sucesso",
                'account' => $account,
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
                throw new OperationException('Erro ao alterar status da conta', BankAccountManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $account = BankAccountManagement::readAccount($request->id);

            if (!$account) {
                throw new OperationException('Erro ao ler conta na operação de alteração de status', BankAccountManagement::getTableName(), "Conta não encontrada: {$request->id}", 409);
            }

            $account->status = 0;
            $account->save();

            return response()->json([
                'status' => 200,
                'msg' => "Conta removida com sucesso",
                'account' => $account
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    function isString($variable)
    {
        if (stripos($variable, ',') !== false) {
            return str_replace(",", ".", str_replace(".", "", $variable));
        } else {
            return $variable;
        }
    }

    public function alterStatus(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da conta', BankAccountManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $account = BankAccountManagement::readAccount($request->id);

            if (!$account) {
                throw new OperationException('Erro ao ler conta na operação de alteração de status', BankAccountManagement::getTableName(), "Conta não encontrada: {$request->id}", 409);
            }

            $account->status = $request->status;
            $account->save();

            $text = $request->status === 1 ? 'ativada' : 'desativada';

            return response()->json([
                'status' => 200,
                'msg' => "Conta {$text} com sucesso",
                'account' => $account
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
