<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Crop;
use App\Models\Admin;
use App\Models\Harvest;
use App\Models\Asset;
use Illuminate\Http\Request;
use App\Models\PropertyCropJoin;
use App\Http\Controllers\Controller;
use App\Exceptions\OperationException;
use App\Models\AssetProperty;
use App\Models\PropertyCropDisease;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;

class AssetController extends Controller
{

    /**
     * Lista as propriedades de um admin
     * @param admin_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            $property = $request->get("property") ?? "";

            list($assets, $total) = Asset::readAssets($admin_id, $property, $filter, $page);

            return response()->json([
                'status' => 200,
                'assets' => $assets,
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

    /**
     * Lê uma propriedade
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read($id = null)
    {
        try {

            if (!$id) {
                throw new OperationException('Erro ao ler bem', Asset::getTableName(), "ID Não enviado", 422);
            }

            $asset = Asset::readAsset($id);

            if ($asset) {
                return response()->json([
                    'status' => 200,
                    'asset' => $asset
                ], 200);
            } else {
                throw new OperationException('Erro ao ler bem', Asset::getTableName(), "Bem não encontrado: {$id}", 409);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Cadastra ou edita uma propriedade
     * @param admin_id
     * @param name
     * @param type
     * @param value
     * @param property_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function form(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'name' => 'required',
                'type' => 'required',
                'value' => 'required',
                'properties' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar bem', Asset::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $asset = Asset::find($request->id);

                if (!$asset) {
                    throw new OperationException('Erro ao ler bem na operação de edição', Asset::getTableName(), "Propriedade não encontrada: {$request->id}", 409);
                }
            } else {
                $asset = new Asset();
            }

            $asset->name = $request->name;
            $asset->type = $request->type;
            $asset->observations = $request->observations ?? "";
            $asset->value = isString($request->value);
            $asset->property_id = $request->property_id ?? 0;
            $asset->image = $request->image ? (gettype($request->image) == "string" ? $request->image : UploadFile($request->image, '/uploads/assets')) : ($request->id ? $asset->image : "");
            $asset->year = $request->year ?? "";
            $asset->buy_date = $request->buy_date ?? "";
            $asset->lifespan = $request->lifespan ?? "";
            $asset->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            AssetProperty::where('asset_id', $asset->id)->delete();

            if ($request->properties) {
                foreach (explode(",", $request->properties) as $property) {
                    $assetProperty = new AssetProperty();
                    $assetProperty->asset_id = $asset->id;
                    $assetProperty->property_id = $property;
                    $assetProperty->save();
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Bem {$text} com sucesso",
                'asset' => $asset
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Altera o status de uma propriedade
     * @param admin_id
     * @param id - id da propriedade
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do bem', Asset::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $asset = Asset::find($request->id);

            if (!$asset) {
                throw new OperationException('Erro ao ler bem na operação de alteração de status', Asset::getTableName(), "Bem não encontrada: {$request->id}", 409);
            }

            $asset->status = 0;
            $asset->save();

            return response()->json([
                'status' => 200,
                'msg' => "Bem removido com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function alterProperty(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
                'property_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar propriedade do bem', Asset::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $asset = Asset::find($request->id);

            if (!$asset) {
                throw new OperationException('Erro ao ler bem na operação de alteração de propriedade', Asset::getTableName(), "Bem não encontrado: {$request->id}", 409);
            }

            if (!$request->id) {
                $admin = Admin::find($request->admin_id);
                $asset->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
            }

            $asset->property_id = $request->property_id ?? null;
            $asset->save();

            return response()->json([
                'status' => 200,
                'msg' => "Bem alterado com sucesso",
                'asset' => $asset
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
