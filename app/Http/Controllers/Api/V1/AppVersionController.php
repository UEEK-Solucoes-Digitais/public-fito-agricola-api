<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function getVersion()
    {
        try {
            $app_version = AppVersion::first();

            if (!$app_version) {
                return response()->json([
                    'status' => 404,
                    'msg' => 'Nenhuma versão encontrada'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'app_version' => $app_version
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
}
