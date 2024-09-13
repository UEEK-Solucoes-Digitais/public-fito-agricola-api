<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\LogError;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function form(Request $request)
    {
        try {

            $log = new LogError();
            $log->error_description = "Erro enviado do sistema/aplicativo";
            $log->environment = $request->environment;
            $log->table_name = "external";
            $log->exception_message = $request->error;
            $log->exception_file = $request->location ?? '';
            $log->exception_line = "";
            $log->save();

            return response()->json([
                'status' => 200,
                'msg' => 'Log de erro salvo com sucesso',
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
