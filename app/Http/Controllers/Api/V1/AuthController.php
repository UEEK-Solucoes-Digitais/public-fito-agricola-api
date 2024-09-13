<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['login', 'register']]);
    }

    /**
     * @lrd:start
     * Rota para pegar bearer token
     * Retorna o token e o tempo de expiração
     * @lrd:end
     * @QAparam email string required
     * @QAparam password string required
     * */
    public function login(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            // return response()->json(['fields' => $request->all()], 401);

            if ($validator->fails()) {
                // createLogError("Erro ao obter token", "auth",  "Campos faltando no request: <br> {$validator->errors()}", pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
                return response()->json(['error' => 'Campos faltando'], 401);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                // createLogError("Erro ao obter token", "auth",  "Campos incorretos", pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
                return response()->json(['error' => 'Acesso não autorizado'], 401);
            }

            return $this->createNewToken($token);
        } catch (\Throwable $e) {
            // createLogError("Erro ao obter token", "auth",  $e->getMessage(), pathinfo($e->getFile())['basename'], $e->getLine());

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => pathinfo($e->getFile())['basename'],
            ], 500);
        }
    }
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL(),
            'user' => auth()->user()
        ]);
    }
}
