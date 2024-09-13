<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Crop;
use App\Models\CropFile;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CropFileController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $admin = Admin::find($admin_id);
            $admin_id_to_filter = null;
            if ($admin->access_level != 1) {
                $admin_id_to_filter = $admin_id;
            }

            list($crops, $total) = Crop::readCrops($admin_id_to_filter, null, null, ['id', 'name', 'property_id']);
            $properties = Property::readPropertiesMinimum($admin_id_to_filter);
            list($crops_files, $total_files) = CropFile::readCropFiles($admin_id_to_filter, $request);

            return response()->json([
                'status' => 200,
                'crops_files' => $crops_files,
                'crops' => $crops,
                'properties' => $properties,
                'total' => $total_files,
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
                throw new OperationException('Erro ao ler lavoura', CropFile::getTableName(), "ID Não enviado", 422);
            }

            $crop_file = CropFile::read($id);

            if ($crop_file) {

                return response()->json([
                    'status' => 200,
                    'crop_file' => $crop_file
                ], 200);
            } else {
                throw new OperationException('Erro ao ler laudo lavoura', CropFile::getTableName(), "Laudo não encontrada: {$id}", 409);
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
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar laudo de lavoura', CropFile::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $crop_file = CropFile::find($request->id);
                if (!$crop_file) {
                    throw new OperationException('Erro ao cadastrar/editar laudo de lavoura', CropFile::getTableName(), "Laudo de lavoura não encontrado", 404);
                }
            } else {
                $crop_file = new CropFile();
            }

            $crop_file->crop_id = $request->crop_id;
            $crop_file->name = $request->name;
            $crop_file->path = $request->path;
            $crop_file->clay = str_replace(',', '.', $request->clay) ?? 0.0;
            $crop_file->organic_material = str_replace(',', '.', $request->organic_material) ?? 0.0;
            $crop_file->base_saturation = str_replace(',', '.', $request->base_saturation) ?? 0.0;
            $crop_file->unit_ca = str_replace(',', '.', $request->unit_ca) ?? 0.0;
            $crop_file->unit_mg = str_replace(',', '.', $request->unit_mg) ?? 0.0;
            $crop_file->unit_al = str_replace(',', '.', $request->unit_al) ?? 0.0;
            $crop_file->unit_k = str_replace(',', '.', $request->unit_k) ?? 0.0;
            $crop_file->unit_p = str_replace(',', '.', $request->unit_p) ?? 0.0;
            $crop_file->status = 1;
            $crop_file->save();

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Laudo de lavoura {$text} com sucesso",
                'crop_file' => $crop_file
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

    public function delete(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do arquivo da lavoura', CropFile::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $file = CropFile::where('status', 1)->where('id', $request->id)->first();

            if (!$file) {
                throw new OperationException('Erro ao ler arquivo dalavoura na operação de alteração de status', CropFile::getTableName(), "Arquivo não encontrado: {$request->id}", 409);
            }

            $file->status = 0;
            $file->save();

            return response()->json([
                'status' => 200,
                'msg' => "Arquivo removido com sucesso",
                'file' => $file
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
