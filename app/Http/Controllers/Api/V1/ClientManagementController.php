<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\ClientManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientManagementController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($clients, $total) = ClientManagement::readClients($admin_id, $filter, $page);

            return response()->json([
                'status' => 200,
                'clients' => $clients,
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
                throw new OperationException('Erro ao ler cliente', ClientManagement::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');

            $client = ClientManagement::readClient($id, $admin_id);

            if ($client) {

                return response()->json([
                    'status' => 200,
                    'client' => $client
                ], 200);
            } else {
                throw new OperationException('Erro ao ler cliente', ClientManagement::getTableName(), "Cliente não encontrada: {$id}", 409);
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
                'seller' => 'required',
                'email' => 'required',
                'phone' => 'required',
                'type' => 'required',
                'document' => 'required',
                'state_registration' => 'required',
                'branch_of_activity' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar cliente', ClientManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $client = ClientManagement::readClient($request->id);

                if (!$client) {
                    throw new OperationException('Erro ao ler cliente na operação de edição', ClientManagement::getTableName(), "Cliente não encontrada: {$request->id}", 409);
                }
            } else {
                $client = new ClientManagement();
            }

            $client->admin_id = $request->admin_id;
            $client->name = $request->name;
            $client->seller = $request->seller;
            $client->email = $request->email;
            $client->phone = $request->phone;
            $client->type = $request->type;
            $client->document = $request->document;
            $client->state_registration = $request->state_registration;
            $client->branch_of_activity = $request->branch_of_activity;
            $client->cep = $request->cep ?? '';
            $client->country = $request->country ?? '';
            $client->corporate_name = $request->corporate_name ?? '';
            $client->state = $request->state ?? '';
            $client->city = $request->city ?? '';
            $client->number = $request->number ?? 0;
            $client->street = $request->street ?? '';
            $client->complement = $request->complement ?? '';
            $client->reference = $request->reference ?? '';

            $client->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Cliente {$text} com sucesso",
                'client' => $client,
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
                throw new OperationException('Erro ao alterar status da cliente', ClientManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $client = ClientManagement::readClient($request->id);

            if (!$client) {
                throw new OperationException('Erro ao ler cliente na operação de alteração de status', ClientManagement::getTableName(), "Cliente não encontrada: {$request->id}", 409);
            }

            $client->status = 0;
            $client->save();

            return response()->json([
                'status' => 200,
                'msg' => "Client removida com sucesso",
                'client' => $client
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
