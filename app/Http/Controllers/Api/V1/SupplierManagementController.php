<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccountManagement;
use App\Models\SupplierManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierManagementController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($suppliers, $total) = SupplierManagement::readSuppliers($admin_id, $filter, $page);
            list($accounts, $_) = BankAccountManagement::readAccounts($admin_id, null, null);
            list($banks, $_) = Bank::readBanks(null, null);

            return response()->json([
                'status' => 200,
                'suppliers' => $suppliers,
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
                throw new OperationException('Erro ao ler fornecedor', SupplierManagement::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');
            $supplier = SupplierManagement::readSupplier($id, $admin_id);
            list($accounts, $_) = BankAccountManagement::readAccounts($supplier->admin_id, null, null);

            if ($supplier) {

                return response()->json([
                    'status' => 200,
                    'supplier' => $supplier,
                    'accounts' => $accounts
                ], 200);
            } else {
                throw new OperationException('Erro ao ler fornecedor', SupplierManagement::getTableName(), "Fornecedor não encontrada: {$id}", 409);
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
                'email' => 'required',
                'type' => 'required',
                'document' => 'required',
                'state_registration' => 'required',
                'branch_of_activity' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar fornecedor', SupplierManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $supplier = SupplierManagement::readSupplier($request->id);

                if (!$supplier) {
                    throw new OperationException('Erro ao ler fornecedor na operação de edição', SupplierManagement::getTableName(), "Fornecedor não encontrada: {$request->id}", 409);
                }
            } else {
                $supplier = new SupplierManagement();
            }

            $supplier->admin_id = $request->admin_id;
            $supplier->name = $request->name;
            $supplier->corporate_name = $request->corporate_name ?? '';
            $supplier->email = $request->email;
            $supplier->phone = $request->phone ?? '';
            $supplier->type = $request->type;
            $supplier->document = $request->document;
            $supplier->state_registration = $request->state_registration;
            $supplier->branch_of_activity = $request->branch_of_activity;
            $supplier->cep = $request->cep;
            $supplier->country = $request->country ?? '';
            $supplier->state = $request->state ?? '';
            $supplier->city = $request->city ?? '';
            $supplier->number = $request->number ?? 0;
            $supplier->street = $request->street ?? '';
            $supplier->complement = $request->complement ?? '';
            $supplier->reference = $request->reference ?? '';

            if ($request->new_account == 'true' || $request->new_account == true) {
                $account = new BankAccountManagement();

                $account->admin_id = $request->admin_id;
                $account->bank_id = $request->bank_id;
                $account->agency = $request->agency;
                $account->account = $request->account;
                $account->type = $request->account_type;
                $account->start_balance = isString($request->start_balance);
                $account->start_date = $request->start_date;
                $account->status = 1;

                $account->save();

                $supplier->account_id = $account->id;
            } else {
                $supplier->account_id = $request->account_id;
            }


            $supplier->save();


            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Fornecedor {$text} com sucesso",
                'supplier' => $supplier,
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
                throw new OperationException('Erro ao alterar status do fornecedor', SupplierManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $supplier = SupplierManagement::readSupplier($request->id);

            if (!$supplier) {
                throw new OperationException('Erro ao ler fornecedor na operação de alteração de status', SupplierManagement::getTableName(), "Fornecedor não encontrada: {$request->id}", 409);
            }

            $supplier->status = 0;
            $supplier->save();

            return response()->json([
                'status' => 200,
                'msg' => "Fornecedor removida com sucesso",
                'supplier' => $supplier
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
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do fornecedor', SupplierManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $supplier = SupplierManagement::readSupplier($request->id);

            if (!$supplier) {
                throw new OperationException('Erro ao ler pessoa na operação de alteração de status', SupplierManagement::getTableName(), "Pessoa não encontrada: {$request->id}", 409);
            }

            $supplier->type = $request->type;
            $supplier->save();

            $text = $request->type === 1 ? 'pessoa física' : 'pessoa jurídica';

            return response()->json([
                'status' => 200,
                'msg' => "Fornecedor alterado para {$text} com sucesso",
                'supplier' => $supplier
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
