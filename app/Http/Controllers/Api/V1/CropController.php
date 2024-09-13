<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Crop;
use App\Models\CropFile;
use App\Models\Harvest;
use App\Models\LogError;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use KMLClass;
use StepanDalecky\KmlParser\Parser;

class CropController extends Controller
{
    public function list($admin_id, Request $request)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";
            $city = $request->get("city") ?? "";
            $property_id = $request->get("property_id") ?? "";
            // $harvest_id = $request->get("harvest_id") ?? "";

            list($crops, $total) = Crop::readCrops($admin_id, $filter, $page, [], $property_id, $city, false);
            list($properties, $total_properties) = Property::readProperties($admin_id, null);

            return response()->json([
                'status' => 200,
                'crops' => $crops,
                'properties' => $properties,
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
                throw new OperationException('Erro ao ler lavoura', Crop::getTableName(), "ID Não enviado", 422);
            }

            $admin_id = request()->get('admin_id');

            $crop = Crop::readCrop($id, $admin_id);

            if ($crop) {

                return response()->json([
                    'status' => 200,
                    'crop' => $crop
                ], 200);
            } else {
                throw new OperationException('Erro ao ler lavoura', Crop::getTableName(), "Lavoura não encontrada: {$id}", 409);
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
        // throw new OperationException('teste', Crop::getTableName(), "teste {$request->all()}", 422);
        // $log = new LogError();
        // $log->error_description = "teste";
        // $log->environment = 1;
        // $log->table_name = "teste";
        // $log->exception_message = "Campos faltando no request: <br>" . json_encode($request->all());
        // $log->exception_file = "teste";
        // $log->exception_line = 1231;
        // $log->save();

        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar lavoura', Crop::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $crop = Crop::readCrop($request->id);

                if (!$crop) {
                    throw new OperationException('Erro ao ler lavoura na operação de edição', Crop::getTableName(), "Lavoura não encontrada: {$request->id}", 409);
                }
            } else {
                $crop = new Crop();
            }

            $area_update = false;
            if ($crop->area != $request->area) {
                $area_update = true;
            }

            $crop->name = $request->name;
            $crop->area = $request->area ? isString($request->area) : 0;
            $crop->city = $request->city ?? "";


            if ($request->kml_file) {
                $kml_class = new KMLClass();

                // $crop->kml_file = UploadFile($request->kml_file, "uploads/crops/");

                try {
                    // dd($kml_class->getCoordinateText($kml_class->getCoordinates("crops/{$request->kml_file}")));
                    $crop->draw_area = $kml_class->getCoordinateText($kml_class->getCoordinates("crops/{$request->kml_file}"));
                } catch (OperationException $e) {
                    report($e);
                    // throw new OperationException('Erro ao cadastrar/editar uma lavoura', Crop::getTableName(), "Ocorreu um erro ao ler o arquivo KML. Entre em contato com o suporte", 409);
                }
            } else if ($request->draw_area) {

                if (!$request->id || ($request->id && $request->draw_area != $crop->draw_area)) {
                    $crop->kml_file = "";
                    $crop->draw_area = $request->draw_area;
                }
            } else if (!$request->id) {
                // report($e);
                // throw new OperationException('Erro ao cadastrar/editar uma lavoura', Crop::getTableName(), "Insira um arquivo KML ou desenhe as coordenadas no mapa", 409);
            }

            $crop->property_id = $request->property_id && $request->property_id != 'null' ? $request->property_id : null;
            $crop->save();

            if ($request->report_files) {
                foreach ($request->report_files as $key => $file) {
                    $crop_file = new CropFile();
                    $crop_file->crop_id = $crop->id;
                    $crop_file->name = $request->report_files_names[$key];
                    $crop_file->path = $file;
                    $crop_file->status = 1;
                    $crop_file->clay = str_replace(',', '.', $request->request_files_clay[$key]) ?? 0.0;
                    $crop_file->organic_material = str_replace(',', '.', $request->request_files_organic_material[$key]) ?? 0.0;
                    $crop_file->base_saturation = str_replace(',', '.', $request->request_files_base_saturation[$key]) ?? 0.0;
                    $crop_file->unit_ca = str_replace(',', '.', $request->request_files_unit_ca[$key]) ?? 0.0;
                    $crop_file->unit_mg = str_replace(',', '.', $request->request_files_unit_mg[$key]) ?? 0.0;
                    $crop_file->unit_al = str_replace(',', '.', $request->request_files_unit_al[$key]) ?? 0.0;
                    $crop_file->unit_k = str_replace(',', '.', $request->request_files_unit_k[$key]) ?? 0.0;
                    $crop_file->unit_p = str_replace(',', '.', $request->request_files_unit_p[$key]) ?? 0.0;
                    $crop_file->save();
                }
            }

            $text = $request->id ? 'editada' : 'cadastrada';

            $has_seed = false;
            $text_seeds = '';

            if ($crop->property && $crop->crops_join->count() > 0 && $area_update) {

                $links = "";

                $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

                foreach ($crop->crops_join as $join) {
                    if ($join->harvest_id == $last_harvest->id && $join->data_seed->count() > 0) {
                        $has_seed = true;
                        $links .= "<a href='/dashboard/propriedades/lavoura/{$join->id}?tab=dados-manejo&subtab=sementes'>Alterar plantio na propriedade {$join->property->name}</a><br>";
                    }
                }

                if ($has_seed) {
                    $text_seeds = "Você alterou a área da lavoura, isso impacta na área de plantio da lavoura no ano agrícola atual.<br>Clique no link abaixo para realizar a alteração no cadastro de sementes.<br> {$links}";
                }
            }

            return response()->json([
                'status' => 200,
                'msg' => "Lavoura {$text} com sucesso",
                'crop' => $crop,
                'has_seed' => $has_seed,
                'text_seeds' => $text_seeds
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
                throw new OperationException('Erro ao alterar status da lavoura', Crop::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $crop = Crop::readCrop($request->id);

            if (!$crop) {
                throw new OperationException('Erro ao ler lavoura na operação de alteração de status', Crop::getTableName(), "Lavoura não encontrada: {$request->id}", 409);
            }

            $crop->status = 0;
            $crop->save();

            return response()->json([
                'status' => 200,
                'msg' => "Lavoura removida com sucesso",
                'crop' => $crop
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
                throw new OperationException('Erro ao alterar propriedade da lavoura', Crop::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $crop = Crop::readCrop($request->id);

            if (!$crop) {
                throw new OperationException('Erro ao ler lavoura na operação de alteração de propriedade', Crop::getTableName(), "Lavoura não encontrada: {$request->id}", 409);
            }

            if (!$request->id) {
                $admin = Admin::find($request->admin_id);
                $crop->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
            }

            $crop->property_id = $request->property_id ?? null;
            $crop->save();

            return response()->json([
                'status' => 200,
                'msg' => "Lavoura alterada com sucesso",
                'crop' => $crop
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
