<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Admin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\AccountCreatedMail;
use App\Mail\ForgotPasswordEmail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\OperationException;
use App\Mail\ConfirmResetPasswordEmail;
use App\Models\AdminNotificationToken;
use App\Models\ContentTypeAccess;
use App\Models\LogError;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login', 'register', 'sendNewPasswordEmail', 'readHash', 'updatePassword']]);
    // }

    public function login(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao fazer login de usuário', Admin::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            $credentials = $request->only(['email', 'password']);
            $credentials['status'] = 1;

            $token = auth()->guard('admins')->attempt($credentials);

            if (!$token) {
                throw new OperationException("Erro ao fazer login de usuário - {$request->email}, {$request->password}", Admin::getTableName(), "As credenciais de login estão incorretas. Verifique seu e-mail e senha", 429);
            }

            $admin = Admin::where('email', $request->email)
                ->with('tokens')
                ->where('status', 1)
                ->first();
            $admin->access_ma = $admin->access_level == 1 || ContentTypeAccess::where('type', 2)->where('admin_id', $admin->id)->first() ? true : false;
            $admin->properties_count = $admin->all_properties_count();

            $token_jwt = auth()->attempt(['email' => config('app.api_email'), 'password' => config('app.api_password')]);

            return response()->json([
                'status' => 200,
                'admin' => $admin,
                'token' => $token_jwt,
                'token_type' => 'bearer',
                'msg' => 'Login realizado com sucesso. Aguarde para ser redirecionado',
                'expires_in' => JWTAuth::factory()->getTTL(),
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function sendNewPasswordEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao enviar email de resetar senha do usuário', Admin::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            $admin = Admin::where('email', $request->email)->where("status", '!=', 0)->first();


            if ($admin) {
                checkSection($admin->id);

                $hash = Hash::make("{$admin->id}:{$admin->name}:{$admin->email}:{$admin->status}");
                $admin->hash = $hash;
                $admin->save();

                Mail::to($admin->email)->send(new ForgotPasswordEmail($admin, $hash));

                return response()->json([
                    'status' => 200,
                    'msg' => 'E-mail enviado com sucesso',
                ], 200);
            } else {
                throw new OperationException('Erro ao enviar email de resetar senha do usuário', Admin::getTableName(), "Usuário não encontrado", 409);
            }
        } catch (OperationException $e) {

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException("Erro ao alterar a senha do usuário", Admin::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            $admin = Admin::where('id', $request->id)->where("status", '!=', 0)->first();
            checkSection($admin->id);

            if ($admin) {
                $admin->password = Hash::make($request->password);
                $admin->save();

                Mail::to($admin->email)->send(new ConfirmResetPasswordEmail($admin));

                return response()->json([
                    'status' => 200,
                    'msg' => 'Senha alterada com sucesso',
                ], 200);
            } else {
                throw new OperationException("Erro ao alterar a senha do usuário", Admin::getTableName(), "Usuário não encontrado - {$request->id}", 409);

                return response()->json([
                    'status' => 409,
                    'msg' => 'Usuário do app nao encontrado',
                ]);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function refresh()
    {
        return $this->createNewToken(JWTAuth::refresh());
    }

    public function verifyToken(Request $request)
    {
        try {
            if (!$admin = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'Usuário não encontrado'], 404);
            }

            return response()->json(compact('admin'));
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido'], 500);
        }
    }

    public function list($admin_id, Request $request)
    {
        try {
            $properties = $request->get("properties") ?? "";
            $page = $request->get("page") ?? "";
            $filter = $request->get("filter") ?? "";
            $filter_type = $request->get("filterType") ?? "";

            $admins = Admin::where('status', '!=', 0)->orderBy('name', 'ASC');

            // if ($properties) {
            //     $admins = $admins->with('all_properties');
            // }

            if ($filter && $filter != 'null') {
                $admins = $admins->where(function ($query) use ($filter) {
                    $query->where('name', 'LIKE', "%{$filter}%")
                        ->orWhere('email', 'LIKE', "%{$filter}%")
                        ->orWhere('phone', 'LIKE', "%{$filter}%");
                });
            }

            if ($filter_type && $filter_type != 'null') {
                $admins = $admins->where('access_level', $filter_type);
            }

            $total = $admins->count();

            if ($page) {
                $skip = ($page - 1) * 10;
                $admins = $admins->skip($skip)->take(100)->get();
            } else {
                $admins = $admins->get();
            }

            if ($admin_id) {
                $admin = Admin::where('id', $admin_id)->where('status', '!=', 0)->first();

                if ($admin && $admin->access_level != 1) {
                    $admins = collect([$admin]);
                    $total = 1;
                }
            }

            if ($properties) {

                $admins->each(function ($admin) {
                    $admin->all_properties = $admin->all_properties();

                    return $admin;
                });
            }

            return response()->json([
                'status' => 200,
                'admins' => $admins,
                'total' => $total,
                'page' => $page
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function read($id)
    {
        try {

            $admin = Admin::where('id', $id)->with('tokens')->where('status', '!=', 0)->first();

            if (!$admin) {
                return response()->json([
                    'status' => 409,
                ], 409);
                // throw new OperationException("Erro ao buscar usuário - {$id}", Admin::getTableName(), "ID não encontrado: {$id}");
            }

            $admin->access_ma = $admin->access_level == 1 || ContentTypeAccess::where('type', 2)->where('admin_id', $admin->id)->first() ? true : false;
            $admin->properties_count = $admin->all_properties_count();

            return response()->json([
                'status' => 200,
                'admin' => $admin,
                'properties' => $admin->all_properties()
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function readHash($hash)
    {
        try {
            $hashToRead = base64_decode($hash);

            $admin = Admin::where('hash', $hashToRead)->where('status', '!=', 0)->first();

            if (!$admin) {
                throw new OperationException("Admin não encontrado", Admin::getTableName(), "HASH não encontrado: {$hashToRead}", 404);
            }

            return response()->json([
                'status' => 200,
                'admin' => $admin
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email',
                'access_level' => 'required'
            ]);
            checkSection($request->admin_id);

            if ($validator->fails()) {
                throw new OperationException('Erro ao criar administrador', Admin::getTableName(), "Campos faltando: {$validator->errors()}", 422);
            }

            if (Admin::where('email', $request->email)->where('status', '!=', 0)->first()) {
                throw new OperationException('Erro ao criar administrador', Admin::getTableName(), "Já existe um administrador cadastrado com esse e-mail", 409);
            } else {
                $password = Str::random(8);

                if ($request->password) {
                    if (strlen($request->password) > 3) {
                        $password = $request->password;
                    } else {
                        throw new OperationException('Erro ao criar administrador', Admin::getTableName(), "Senha deve conter mais de 3 digitos", 422);
                    }
                }

                if ($request->access_level === 0) {
                    throw new OperationException('Erro ao criar administrador', Admin::getTableName(), "Nenhum tipo de usuário selecionado", 422);
                }

                $admin = new Admin();
                $admin->name = $request->name;
                $admin->email = $request->email;
                $admin->password = Hash::make($password);
                $admin->phone = $request->phone ?? "";
                $admin->city = $request->city ?? null;
                $admin->state = $request->state ?? null;
                $admin->country = $request->country ?? null;
                $admin->access_level = $request->access_level;
                $admin->profile_picture = $request->profile_picture ?? '';
                $admin->cpf = $request->cpf ?? "";
                $admin->properties_available = $request->properties_available ?? 0;

                switch ($request->access_level) {
                    case 1:
                        $admin->level = ADMIN::ADMIN_ACCESS;
                        break;
                    case 2:
                        $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                        break;
                    case 3:
                        $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                        break;
                    case 4:
                        $admin->level = ADMIN::MA_ACCESS;
                        break;
                    case 5:
                        $admin->level = ADMIN::TEAM_ACCESS;
                        break;
                }

                $admin->save();

                if ($request->access_level == 4) {
                    ContentTypeAccess::firstOrCreate([
                        'admin_id' => $admin->id,
                        'type' => 2
                    ]);
                } else {
                    ContentTypeAccess::where('admin_id', $admin->id)->where('type', 2)->delete();
                }

                // Mail::to($admin->email)->send(new AccountCreatedMail($admin, $password));

                return response()->json([
                    'status' => 200,
                    'msg' => 'Usuário adicionado com sucesso',
                    'admin' => $admin,
                ], 200);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function update(Request $request)
    {
        try {

            // return response()->json([
            //     'status' => 500,
            //     'msg' => $request->name,
            // ], 500);

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao editar administrador', Admin::getTableName(), "Campos faltando: {$validator->errors()}", 422);
            }
            // checkSection($request->admin_id);

            $admin = Admin::find($request->id);

            checkSection($request->admin_id ?? $admin->id);

            if ($admin) {

                if ($request->email != $admin->email && Admin::where('email', $request->email)->where('id', '!=', $admin->id)->where('status', '!=', 0)->first()) {
                    throw new OperationException('Erro ao editar administrador', Admin::getTableName(), "Já existe um administrador cadastrado com esse e-mail", 409);
                }

                $admin->name = $request->name;
                // $admin->name = preg_replace('/[^A-Za-z0-9\-]/', '', $request->name);
                $admin->email = $request->email;
                $admin->cpf = $request->cpf ?? "";
                $admin->phone = $request->phone ?? "";
                $admin->profile_picture = $request->profile_picture ? (gettype($request->profile_picture) == 'object' ? UploadFile($request->profile_picture, "/uploads/admins") : $request->profile_picture) : $admin->profile_picture;
                $admin->properties_available = $request->properties_available ?? 0;
                $admin->city = $request->city ?? null;
                $admin->state = $request->state ?? null;
                $admin->country = $request->country ?? null;

                if ($request->access_level) {
                    if ($admin->access_level != $request->access_level) {

                        switch ($request->access_level) {
                            case 1:
                                $admin->level = ADMIN::ADMIN_ACCESS;
                                break;
                            case 2:
                                $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                                break;
                            case 3:
                                $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                                break;
                            case 4:
                                $admin->level = ADMIN::MA_ACCESS;
                                break;
                            case 5:
                                $admin->level = ADMIN::TEAM_ACCESS;
                                break;
                        }
                    } else {
                        $admin->level = $request->level ?? "";
                        // $admin->level = join(",", $request->level);
                    }

                    $admin->access_level = $request->access_level;
                }

                $admin->status = $request->status;

                if ($request->password && $request->password != "") {
                    $admin->password =  Hash::make($request->password);
                }

                $admin->updated_at = date('Y-m-d H:i:s');
                $admin->save();

                if (isset($request->access_ma)) {
                    if ($request->access_ma == 'true') {
                        ContentTypeAccess::firstOrCreate([
                            'admin_id' => $admin->id,
                            'type' => 2
                        ]);
                    } else {
                        ContentTypeAccess::where('admin_id', $admin->id)->where('type', 2)->delete();
                    }
                }

                return response()->json([
                    'status' => 200,
                    'msg' => 'Usuário alterado com sucesso',
                    'admin' => $admin,
                ], 200);
            } else {
                throw new OperationException('Erro ao editar administrador', Admin::getTableName(), "Administrador não encontrado", 404);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            checkSection($request->admin_id);

            if ($validator->fails()) {
                throw new OperationException('Erro ao excluir administrador', Admin::getTableName(), "Campos faltando: {$validator->errors()}", 422);
            }


            $admin = Admin::findOrFail($request->id);
            $admin->status = 0;
            $admin->updated_at = date('Y-m-d H:i:s');
            $admin->save();

            return response()->json([
                'status' => 200,
                'msg' => 'Administrador removido com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function alterAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao excluir administrador', Admin::getTableName(), "Campos faltando: {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $admin = Admin::findOrFail($request->id);

            if ($request->access_level) {
                $admin->access_level = $request->access_level;

                switch ($request->access_level) {
                    case 1:
                        $admin->level = ADMIN::ADMIN_ACCESS;
                        break;
                    case 2:
                        $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                        break;
                    case 3:
                        $admin->level = ADMIN::CONSULTANT_AND_PRODUCER_ACCESS;
                        break;
                    case 4:
                        $admin->level = ADMIN::MA_ACCESS;
                        break;
                    case 5:
                        $admin->level = ADMIN::TEAM_ACCESS;
                        break;
                }
            } else {
                $admin->status = $request->status;
            }

            $admin->save();

            if ($request->access_level == 4) {
                ContentTypeAccess::firstOrCreate([
                    'admin_id' => $admin->id,
                    'type' => 2
                ]);
            }
            // else {
            //     ContentTypeAccess::where('admin_id', $admin->id)->where('type', 2)->delete();
            // }

            return response()->json([
                'status' => 200,
                'msg' => 'Administrador removido com sucesso',
                'admin' => $admin,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function updateNotificationToken(Request $request)
    {
        try {
            checkSection($request->user_id);

            $admin = Admin::find($request->user_id);
            // $admin->notifications_token = $request->notification_token;
            // $admin->save();

            if ($admin->tokens()->where('token', $request->notification_token)->first()) {
                $token = $admin->tokens()->where('token', $request->notification_token)->first();

                $token->device_id = $request->device_id;
                $token->save();
            } else {
                $admin->tokens()->create([
                    'token' => $request->notification_token,
                    'device_id' => $request->device_id
                ]);
            }

            $admin = Admin::where('id', $request->user_id)->with('tokens')->where('status', '!=', 0)->first();
            $admin->access_ma = $admin->access_level == 1 || ContentTypeAccess::where('type', 2)->where('admin_id', $admin->id)->first() ? true : false;
            $admin->properties_count = $admin->all_properties_count();

            return response()->json([
                'status' => 200,
                'admin' => $admin,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function removeNotificationToken(Request $request)
    {
        try {
            checkSection($request->admin_id);

            $admin = Admin::find($request->admin_id);

            if ($admin->tokens()->where('token', $request->notification_token)->first()) {
                $token = $admin->tokens()->where('token', $request->notification_token)->first();

                AdminNotificationToken::where("device_id", $token->device_id)->delete();
            }

            return response()->json([
                'status' => 200,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function updateActualHarvest(Request $request)
    {
        try {
            checkSection($request->admin_id);

            $admin = Admin::find($request->admin_id);

            $admin->actual_harvest_id = $request->harvest_id == 'null' ? null : $request->harvest_id;
            $admin->save();

            return response()->json([
                'status' => 200,
                'msg' => 'Registro atualizado com sucesso',
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }

    public function updateAttribute(Request $request)
    {
        try {
            checkSection($request->admin_id);

            $attribute = $request->attribute;

            $admin = Admin::find($request->admin_id);
            $admin->$attribute = $request->value;
            $admin->save();

            return response()->json([
                'status' => 200,
                'admin' => $admin,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() === 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], 500);
        }
    }
}
