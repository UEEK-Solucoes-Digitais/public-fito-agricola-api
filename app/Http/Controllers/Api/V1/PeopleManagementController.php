<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\PeopleManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeopleManagementController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($people, $total) = PeopleManagement::readPeople($admin_id, $filter, $page);

            return response()->json([
                'status' => 200,
                'people' => $people,
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
                throw new OperationException('Erro ao ler pessoa', PeopleManagement::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');

            $person = PeopleManagement::readPerson($id, $admin_id);

            if ($person) {

                return response()->json([
                    'status' => 200,
                    'person' => $person
                ], 200);
            } else {
                throw new OperationException('Erro ao ler pessoa', PeopleManagement::getTableName(), "Pessoa não encontrada: {$id}", 409);
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
                'email' => 'required|email',
                'type' => 'required|integer',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar pessoa', PeopleManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $person = PeopleManagement::readPerson($request->id);

                if (!$person) {
                    throw new OperationException('Erro ao ler pessoa na operação de edição', PeopleManagement::getTableName(), "Pessoa não encontrada: {$request->id}", 409);
                }
            } else {
                $person = new PeopleManagement();
            }

            $person->name = $request->name;
            $person->email = $request->email;
            $person->phone = $request->phone;
            $person->type = $request->type;
            $person->status = $request->status;
            $person->file = $request->file;
            $person->admin_id = $request->admin_id;
            $person->save();

            
            $text = $request->id ? 'editada' : 'cadastrada';

            return response()->json([
                'status' => 200,
                'msg' => "Pessoa {$text} com sucesso",
                'person' => $person,
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
                throw new OperationException('Erro ao alterar status da pessoa', PeopleManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $person = PeopleManagement::readPerson($request->id);

            if (!$person) {
                throw new OperationException('Erro ao ler pessoa na operação de alteração de status', PeopleManagement::getTableName(), "Pessoa não encontrada: {$request->id}", 409);
            }

            $person->status = 0;
            $person->save();

            return response()->json([
                'status' => 200,
                'msg' => "Pessoa removida com sucesso",
                'person' => $person
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
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da pessoa', PeopleManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $person = PeopleManagement::readPerson($request->id);

            if (!$person) {
                throw new OperationException('Erro ao ler pessoa na operação de alteração de status', PeopleManagement::getTableName(), "Pessoa não encontrada: {$request->id}", 409);
            }

            $person->status = $request->status;
            $person->save();

            $text = $request->status === 1 ? 'ativada' : 'desativada';

            return response()->json([
                'status' => 200,
                'msg' => "Pessoa {$text} com sucesso",
                'person' => $person
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function deleteFile(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do contrato da pessoa', PeopleManagement::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            PeopleManagement::where('status', 1)->update(['file' => null]);

            return response()->json([
                'status' => 200,
                'msg' => "Arquivo removido com sucesso",
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
