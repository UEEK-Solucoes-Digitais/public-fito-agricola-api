<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\PropertyCropJoin;
use App\Models\PropertyCropRainGauge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyRainGaugeController extends Controller
{
    public function form(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar registro pluvial', PropertyCropRainGauge::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $property_crop_rain_gauge = PropertyCropRainGauge::find($request->id);

                if (!$property_crop_rain_gauge) {
                    throw new OperationException('Erro ao ler registro pluvial na operação de edição', PropertyCropRainGauge::getTableName(), "Registro pluvial não encontrado: {$request->id}", 409);
                }

                $property_crop_rain_gauge->properties_crops_id = $request->property_crop_join_id;
                $property_crop_rain_gauge->volume = str_replace(",", ".", $request->volume);
                $property_crop_rain_gauge->date = $request->date;
                $property_crop_rain_gauge->save();
            } else {

                foreach ($request->volumes as $key => $volume) {

                    if ($request->crops) {
                        // $harvest = $request->harvest_id ? Harvest::find($request->harvest_id) : Harvest::where('status', 1)->where('is_last_harvest', 1)->first();
                        $crops_array = gettype($request->crops) == "string" ?  explode(",", $request->crops) : $request->crops;

                        foreach ($crops_array as $crop) {
                            // $property_crop_join = PropertyCropJoin::where('property_id', $request->property_id)
                            //     ->where('harvest_id', $harvest->id)
                            //     ->where('crop_id', isset($crop['id']) ? $crop['id'] : $crop)
                            //     ->where('status', 1)
                            //     ->first();

                            $property_crop_rain_gauge = new PropertyCropRainGauge();
                            $property_crop_rain_gauge->properties_crops_id = isset($crop['id']) ? $crop['id'] : $crop;
                            $property_crop_rain_gauge->volume = str_replace(",", ".", $volume);
                            $property_crop_rain_gauge->date = $request->dates[$key];
                            $property_crop_rain_gauge->save();
                            self::addNotification($property_crop_rain_gauge, $volume);
                        }
                    } else {
                        $property_crop_rain_gauge = new PropertyCropRainGauge();
                        $property_crop_rain_gauge->properties_crops_id = $request->property_crop_join_id;
                        $property_crop_rain_gauge->volume = str_replace(",", ".", $volume);
                        $property_crop_rain_gauge->date = $request->dates[$key];
                        $property_crop_rain_gauge->save();

                        self::addNotification($property_crop_rain_gauge, $volume);
                    }
                }
            }

            $text = $request->id ? 'editado' : 'cadastrado';

            return response()->json([
                'status' => 200,
                'msg' => "Registro pluvial {$text} com sucesso",
                'property_crop_rain_gauge' => $property_crop_rain_gauge
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
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
                throw new OperationException('Erro ao alterar status do registro pluvial', PropertyCropRainGauge::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $property_crop_rain_gauge = PropertyCropRainGauge::find($request->id);

            if (!$property_crop_rain_gauge) {
                throw new OperationException('Erro ao ler registro pluvial na operação de alteração de status', PropertyCropRainGauge::getTableName(), "Registro pluvial não encontrado: {$request->id}", 409);
            }

            $property_crop_rain_gauge->status = 0;
            $property_crop_rain_gauge->save();

            return response()->json([
                'status' => 200,
                'msg' => "Registro pluvial removido com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function addNotification($object, $volume)
    {
        try {
            $title = "{$object->property_crop->property->name} - {$object->property_crop->crop->name} - Chuva - {$volume}mm";
            $text = "Clique para entrar na propriedade.";

            $admin_section = session(config('app.session_name') . "_admin_id");
            $is_equal_admin = false;

            if ($object->property_crop->property->admin->id == $admin_section) {
                $is_equal_admin = true;
            }

            foreach ($object->property_crop->property->admins as $admin) {
                if ($admin->id == $admin_section) {
                    $is_equal_admin = true;
                }

                createNotification($title, $text, 0, $admin->id, $object->property_crop->id, "informations");
            }

            createNotification($title, $text, 0, $object->property_crop->property->admin->id, $object->property_crop->id, "informations");

            if (!$is_equal_admin) {
                createNotification($title, $text, 0, $admin_section, $object->property_crop->id, "informations");
            }
        } catch (OperationException $e) {
            report($e);
        }
    }
}
