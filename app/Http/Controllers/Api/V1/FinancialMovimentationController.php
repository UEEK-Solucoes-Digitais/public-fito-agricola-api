<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Bank;
use App\Models\BankAccountManagement;
use App\Models\ClientManagement;
use App\Models\FinancialCategory;
use App\Models\FinancialInjection;
use App\Models\FinancialMovimentation;
use App\Models\FinancialMovimentationCharge;
use App\Models\FinancialPaymentMethod;
use App\Models\FinancialTaxType;
use App\Models\FinancialTransfer;
use App\Models\FinancialTransferFile;
use App\Models\PeopleManagement;
use App\Models\SupplierManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialMovimentationController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {

            list($movimentations, $init_date, $end_date) = FinancialMovimentation::readAll($admin_id, $request);

            $text_date = "Exibindo movimentações de " . date('d/m/Y', strtotime($init_date)) . " até " . date('d/m/Y', strtotime($end_date));

            $admins = Admin::where('status', '!=', 0)->orderBy('name', 'ASC')->get();
            $admin = Admin::find($admin_id);

            if ($admin && $admin->access_level != 1) {
                $admins = collect([$admin]);
            }

            return response()->json([
                'status' => 200,
                'movimentations' => $movimentations,
                'admins' => $admins,
                'text_date' => $text_date,
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

    public function listItemsForm($admin_id, Request $request)
    {
        try {
            $suppliers = [];
            $clients = [];
            $people = [];
            $tax_types = [];
            $categories = [];
            $banks = [];
            $accounts = [];
            $payment_methods = FinancialPaymentMethod::readAll($admin_id);

            list($accounts, $total) = BankAccountManagement::readAccounts($admin_id, null, null);

            if ($request->get("clients")) {
                list($clients, $total) = ClientManagement::readClients($admin_id, null, null, true);
            }

            if ($request->get("cost")) {
                list($suppliers, $total) = SupplierManagement::readSuppliers($admin_id, null, null, true);
                list($people, $total) = PeopleManagement::readPeople($admin_id, null, null, true);
                $tax_types = FinancialTaxType::readAll();
                $categories = FinancialCategory::readAll();
            }

            if ($request->get("transfer")) {
                list($banks, $total) = Bank::readBanks(null, null);
            }

            return response()->json([
                'status' => 200,
                'accounts' => $accounts,
                'banks' => $banks,
                'payment_methods' => $payment_methods,
                'suppliers' => $suppliers,
                'people' => $people,
                'tax_types' => $tax_types,
                'categories' => $categories,
                'clients' => $clients,
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

    public function read($id, Request $request)
    {
        try {
            if (in_array($request->get("type"), [1, 2, 3])) {
                $movimentation = FinancialMovimentation::readOne($id);
            } else if ($request->get("type") == 4) {
                $movimentation = FinancialInjection::readOne($id);
            } else if ($request->get("type") == 5) {
                $movimentation = FinancialTransfer::readOne($id);
            }

            return response()->json([
                'status' => 200,
                'movimentation' => $movimentation,
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

    public function formMovimentation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar movimentação', FinancialMovimentation::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $movimentation = FinancialMovimentation::find($request->id);

                if (!$movimentation) {
                    throw new OperationException('Erro ao ler movimentação na operação de edição', FinancialMovimentation::getTableName(), "Movimentação não encontrada: {$request->id}", 409);
                }
            } else {
                $movimentation = new FinancialMovimentation();
            }

            $movimentation->sale_title = $request->sale_title ?? '';
            $movimentation->bill_file = $request->bill_file ?? '';
            $movimentation->bill_number = $request->bill_number ?? 0;
            $movimentation->observations = $request->observations ?? '';
            $movimentation->total_value = isString($request->total_value) ?? 0;
            $movimentation->date = $request->date ?? $request->due_date;
            $movimentation->due_date = $request->due_date;
            $movimentation->last_due_date = $request->last_due_date ?? null;
            $movimentation->type = $request->type;
            $movimentation->subtype = $request->subtype ?? 0;
            $movimentation->payment_type = $request->payment_type;
            $movimentation->conditions = $request->conditions ?? 0;
            $movimentation->period = $request->period ?? 0;
            $movimentation->admin_id = $request->admin_id;
            $movimentation->client_management_id = $request->client_management_id && $request->client_management_id != 0 ? $request->client_management_id : null;
            $movimentation->financial_category_id = $request->financial_category_id && $request->financial_category_id != 0 ? $request->financial_category_id : null;
            $movimentation->financial_tax_type_id = $request->financial_tax_type_id && $request->financial_tax_type_id != 0 ? $request->financial_tax_type_id : null;
            $movimentation->supplier_management_id = $request->supplier_management_id && $request->supplier_management_id != 0 ? $request->supplier_management_id : null;
            $movimentation->people_management_id = $request->people_management_id && $request->people_management_id != 0 ? $request->people_management_id : null;
            $movimentation->bank_account_management_id = $request->bank_account_management_id && $request->bank_account_management_id != 0 ? $request->bank_account_management_id : null;
            $movimentation->financial_payment_method_id = $request->financial_payment_method_id;
            $movimentation->save();

            if ($request->installments) {
                $not_delete = [];

                foreach ($request->installments as $key => $installment) {
                    $charge = $installment['id'] ? FinancialMovimentationCharge::find($installment['id']) : new FinancialMovimentationCharge();
                    $charge->financial_movimentation_id = $movimentation->id;
                    $charge->due_date   = $installment['due_date'];
                    $charge->income_date   = $installment['income_date'];
                    $charge->value  = isString($installment['value']);
                    $charge->financial_payment_method_id = $installment['financial_payment_method_id'];
                    $charge->installment = $key + 1;
                    $charge->save();

                    $not_delete[] = $charge->id;
                }

                FinancialMovimentationCharge::where('financial_movimentation_id', $movimentation->id)->whereNotIn('id', $not_delete)->update(['status' => 0]);
            }

            return response()->json([
                'status' => 200,
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

    public function deleteMovimentation(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da movimentação', FinancialMovimentation::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->object_type == 'movimentation') {
                $movimentation = FinancialMovimentation::where('status', 1)->where('id', $request->id)->first();

                if (!$movimentation) {
                    throw new OperationException('Erro ao ler movimentação na operação de alteração de status', FinancialMovimentation::getTableName(), "Arquivo não encontrado: {$request->id}", 409);
                }

                $movimentation->status = 0;
                $movimentation->save();
            } else if ($request->object_type == 'charge') {
                $charge = FinancialMovimentationCharge::where('status', 1)->where('id', $request->id)->first();

                $movimentation = $charge->movimentation;

                if (!$charge) {
                    throw new OperationException('Erro ao ler charge na operação de alteração de status', FinancialMovimentationCharge::getTableName(), "Arquivo não encontrado: {$request->id}", 409);
                }

                $charge->status = 0;
                $charge->save();

                self::adjustInstallments($movimentation, $movimentation->charges);
            } else if ($request->object_type == 'injection') {
                $injection = FinancialInjection::where('status', 1)->where('id', $request->id)->first();

                if (!$injection) {
                    throw new OperationException('Erro ao ler movimentação na operação de alteração de status', FinancialInjection::getTableName(), "Arquivo não encontrado: {$request->id}", 409);
                }

                $injection->status = 0;
                $injection->save();
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function deleteTransferFile(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da movimentação', FinancialMovimentation::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $file = FinancialTransferFile::where('status', 1)->where('id', $request->id)->first();

            if (!$file) {
                throw new OperationException('Erro ao ler arquivo na operação de alteração de status', FinancialTransferFile::getTableName(), "Arquivo não encontrado: {$request->id}", 409);
            }

            $file->status = 0;
            $file->save();

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function formInjection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar movimentação', FinancialInjection::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $injection = FinancialInjection::find($request->id);

                if (!$injection) {
                    throw new OperationException('Erro ao ler movimentação na operação de edição', FinancialInjection::getTableName(), "Movimentação não encontrada: {$request->id}", 409);
                }
            } else {
                $injection = new FinancialInjection();
            }

            $injection->due_date = $request->due_date;
            $injection->investor = $request->investor;
            $injection->value = isString($request->value);
            $injection->admin_id = $request->admin_id;
            $injection->save();

            return response()->json([
                'status' => 200,
                'title' => 'Operação realizada com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function formCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar categoria', FinancialCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $category = FinancialCategory::find($request->id);

                if (!$category) {
                    throw new OperationException('Erro ao ler categoria na operação de edição', FinancialCategory::getTableName(), "Categoria não encontrada: {$request->id}", 409);
                }
            } else {
                $category = new FinancialCategory();
            }

            $category->name = $request->name;
            $category->type = $request->type;
            $category->save();

            return response()->json([
                'status' => 200,
                'title' => 'Operação realizada com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    public function formTransfer(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar categoria', FinancialTransfer::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $transfer = FinancialTransfer::find($request->id);

                if (!$transfer) {
                    throw new OperationException('Erro ao ler categoria na operação de edição', FinancialTransfer::getTableName(), "Categoria não encontrada: {$request->id}", 409);
                }
            } else {
                $transfer = new FinancialTransfer();
            }

            $transfer->admin_id = $request->admin_id;
            $transfer->due_date = $request->due_date;
            $transfer->value = isString($request->value);
            $transfer->type = $request->type;
            $transfer->observations = $request->observations ?? '';
            $transfer->external_account_agency = $request->external_account_agency ?? '';
            $transfer->external_account_account = $request->external_account_account ?? '';
            $transfer->origin_bank_account_id = $request->origin_bank_account_id;
            $transfer->destiny_bank_account_id = $request->destiny_bank_account_id && $request->destiny_bank_account_id  != 0 ? $request->destiny_bank_account_id  : null;
            $transfer->external_account_bank_id = $request->external_account_bank_id && $request->external_account_bank_id  != 0 ? $request->external_account_bank_id  : null;
            $transfer->save();

            if ($request->files_transfer) {
                foreach ($request->files_transfer as $key => $file) {
                    FinancialTransferFile::create([
                        'financial_transfer_id' => $transfer->id,
                        'file' => $file,
                    ]);
                }
            }

            return response()->json([
                'status' => 200,
                'title' => 'Operação realizada com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function conciliate(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar categoria', FinancialTransfer::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            foreach ($request->movimentations as $movimentation) {
                switch ($movimentation['type']) {
                    case 'movimentation':
                        $mov = FinancialMovimentation::find($movimentation['id']);
                        $mov->is_conciliated = 1;
                        $mov->save();
                        break;
                    case 'injection':
                        $injection = FinancialInjection::find($movimentation['id']);
                        $injection->is_conciliated = 1;
                        $injection->save();
                        break;
                    case 'transfer':
                        $transfer = FinancialTransfer::find($movimentation['id']);
                        $transfer->is_conciliated = 1;
                        $transfer->save();
                        break;
                    case 'charge':
                        $charge = FinancialMovimentationCharge::find($movimentation['id']);
                        $charge->is_conciliated = 1;
                        $charge->save();
                        break;
                }
            }

            return response()->json([
                'status' => 200,
                'title' => 'Operação realizada com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function adjustInstallments($movimentation, $charges)
    {
        $movimentation->conditions = count($charges);
        $movimentation->save();

        $conditions = $movimentation->conditions;

        // ajustando parcelas
        $total_value = $movimentation->total_value;
        $installment_value = round($total_value / $conditions, 2);

        FinancialMovimentationCharge::where('financial_movimentation_id', $movimentation->id)->update(['status' => 0]);

        for ($i = 1; $i <= $conditions; $i++) {
            $difference = 0;

            if ($i == $conditions) {
                $difference = $total_value - ($installment_value * $conditions);
            }

            $charge = new FinancialMovimentationCharge();
            $charge->financial_movimentation_id = $movimentation->id;
            $charge->due_date = self::getDateInstallment($movimentation, $i);
            $charge->income_date = self::getDateInstallment($movimentation, $i, true);
            $charge->value = $installment_value + $difference;
            $charge->financial_payment_method_id = $charges[0]->financial_payment_method_id;
            $charge->installment = $i;
            $charge->save();
        }
    }

    public static function getDateInstallment($movimentation, $index, $income = false)
    {

        // se for income, é 7 dias antes da data de vencimento
        if ($income) {
            return date('Y-m-d', strtotime("-7 day", strtotime(date('Y-m-d', strtotime("+$index month", strtotime($movimentation->due_date))))));
        }

        return date('Y-m-d', strtotime("+$index month", strtotime($movimentation->due_date)));
    }
}
