<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\LogError;
use App\Models\Notification;
use App\Models\Property;
use App\Models\PropertyCropDisease;
use App\Models\PropertyCropGallery;
use App\Models\PropertyCropJoin;
use App\Models\PropertyCropObservation;
use App\Models\PropertyCropPest;
use App\Models\PropertyCropStage;
use App\Models\PropertyCropWeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;

use Illuminate\Support\Facades\Log;
use KMLClass;

class PropertyMonitoringController extends Controller
{


    /*
    *  alterar data de todos os monitoramentos informados na data nova
    * @param property_crop_join_id
    * @param date
    * @param new_date
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function changeDate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_crop_join_id' => 'required',
                'date' => 'required',
                'new_date' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar data de monitoramentos', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            $date = date('Y-m-d', strtotime($request->date));
            $new_date = date('Y-m-d', strtotime($request->new_date));

            if ($request->change_stage) {
                PropertyCropStage::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['open_date' => $new_date]);
            }

            if ($request->change_disease) {
                PropertyCropDisease::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['open_date' => $new_date]);
            }

            if ($request->change_pest) {
                PropertyCropPest::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['open_date' => $new_date]);
            }

            if ($request->change_weed) {
                PropertyCropWeed::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['open_date' => $new_date]);
            }

            if ($request->change_observation) {
                PropertyCropObservation::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['open_date' => $new_date]);
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função geral que vai cadastrar o respectivo tipo de monitoramento
    * @param type (1 - estádio, 2 - doença, 3 - praga, 4 - daninha, 5 - observação)
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */

    public function form(Request $request)
    {
        try {
            $success = [];
            $failes = [];

            $crops_array = explode(",", $request->crops);

            foreach ($crops_array as $crop) {
                $property_crop_join = PropertyCropJoin::find($crop);

                if ($property_crop_join) {
                    $request->request->remove('property_crop_join_id');
                    $request->merge(['property_crop_join_id' => $property_crop_join->id]);

                    switch ($request->type) {
                        case '1':
                            $operation = self::formStage($request);

                            if ($operation) {
                                array_push($success, $crop);
                            } else {
                                array_push($failes, $crop);
                            }
                            break;
                        case '2':
                            $operation = self::formDisease($request);

                            if ($operation) {
                                array_push($success, $crop);
                            } else {
                                array_push($failes, $crop);
                            }
                            break;
                        case '3':
                            $operation = self::formPest($request);

                            if ($operation) {
                                array_push($success, $crop);
                            } else {
                                array_push($failes, $crop);
                            }
                            break;
                        case '4':
                            $operation = self::formWeed($request);

                            if ($operation) {
                                array_push($success, $crop);
                            } else {
                                array_push($failes, $crop);
                            }
                            break;
                        case '5':
                            $operation = self::formObservations($request);

                            if ($operation) {
                                array_push($success, $crop);
                            } else {
                                array_push($failes, $crop);
                            }
                            break;
                    }
                }
            }

            if (count($failes) == 0) {
                return response()->json([
                    'status' => 200,
                    'msg' => "Operação realizada com sucesso",
                ], 200);
            } else {
                $text = json_encode($failes);
                throw new OperationException("Erro ao cadastrar/editar lançamento (monitoramento): $text", 'monitoring', "Alguns monitoramentos não puderam ser cadastrados", 500);
            }
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar/editar estádio
    * @param admin_id
    * @param property_crop_join_id
    * @param stages
    * @return \Illuminate\Http\JsonResponse
    */
    public static function formStage(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
                'stages' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar estadio', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $id_all = [];
            $id_images = [];

            $stagesLoop = gettype($request->stages) == 'string' ? json_decode($request->stages) : $request->stages;

            foreach ($stagesLoop as $key => $stage) {
                $stage = (object) $stage;

                if ($stage->id) {
                    $property_crop_stage = PropertyCropStage::find($stage->id);

                    if (!$property_crop_stage) {
                        throw new OperationException('Erro ao ler estadio na operação de edição', PropertyCropStage::getTableName(), "Estádio não encontrado: {$stage->id}", 409);
                    }
                } else {
                    $property_crop_stage = new PropertyCropStage();
                }

                $map_latitude = isset($stage->latitude) ? $stage->latitude : null;
                $map_longitude = isset($stage->longitude) ? $stage->longitude : null;

                $property_crop_stage->admin_id = $request->admin_id;
                $property_crop_stage->open_date = $request->open_date ?? date('Y-m-d');
                $property_crop_stage->properties_crops_id = $request->property_crop_join_id;
                $property_crop_stage->vegetative_age_value = $stage->vegetative_age_value;
                $property_crop_stage->vegetative_age_text = $stage->vegetative_age_text ?? "";
                // $property_crop_stage->vegetative_age_period = $stage->vegetative_age_period;
                $property_crop_stage->reprodutive_age_value = $stage->reprodutive_age_value;
                $property_crop_stage->reprodutive_age_text = $stage->reprodutive_age_text ?? "";
                // $property_crop_stage->reprodutive_age_period = $stage->reprodutive_age_period;
                $property_crop_stage->risk = $stage->risk;
                $property_crop_stage->kml_file = isset($stage->kml_file) && $stage->kml_file ? UploadFile($stage->kml_file, "uploads/property_crop_stages/") : null;

                $kml_class = new KMLClass();
                $coordinates = $property_crop_stage->kml_file ? $kml_class->getCoordinates("property_crop_stages/{$property_crop_stage->kml_file}") : null;

                if ($map_latitude && $map_longitude) {
                    $property_crop_stage->coordinates = new Point(floatval($map_latitude), floatval($map_longitude));
                } else {
                    $property_crop_stage->coordinates = $coordinates && count($coordinates) > 0 ? new Point(floatval($coordinates[0][0]), floatval($coordinates[0][1])) : null;
                }

                $property_crop_stage->save();

                $id_all[$key] = $property_crop_stage->id;

                $images = $request->hasFile('stages_images') ? $request->file('stages_images') : (isset($stage->images) ? $stage->images : null);


                if ($key == 0 && $images) {

                    $id_images[$key] = self::createImages($images, $property_crop_stage->id, 1, "property_crop_stages");
                }
                if (!$stage->id) {
                    self::addNotification($property_crop_stage, 'stage');
                }
            }

            return true;

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                'id_all' => $id_all,
                'id_images' => $id_images,
            ], 200);
        } catch (OperationException $e) {
            report($e);
            return false;
            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar/editar doença
    * @param admin_id
    * @param property_crop_join_id
    * @param diseases
    * @return \Illuminate\Http\JsonResponse
    */

    public static function formDisease(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
                'diseases' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar doença (monitoramento)', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $id_all = [];
            $id_images = [];

            $diseasesLoop = gettype($request->diseases) == 'string' ? json_decode($request->diseases) : $request->diseases;

            foreach ($diseasesLoop as $key => $disease) {
                $disease = (object) $disease;

                if ($disease->id) {
                    $property_crop_disease = PropertyCropDisease::find($disease->id);

                    if (!$property_crop_disease) {
                        throw new OperationException('Erro ao ler doença (monitoramento) na operação de edição', PropertyCropDisease::getTableName(), "Doença não encontrada: {$disease->id}", 409);
                    }
                } else {
                    $property_crop_disease = new PropertyCropDisease();
                }

                $map_latitude = isset($disease->latitude) ? $disease->latitude : null;
                $map_longitude = isset($disease->longitude) ? $disease->longitude : null;

                $property_crop_disease->admin_id = $request->admin_id;
                $property_crop_disease->open_date = $request->open_date ?? date('Y-m-d');
                $property_crop_disease->properties_crops_id = $request->property_crop_join_id;
                $property_crop_disease->interference_factors_item_id = $disease->interference_factors_item_id;
                $property_crop_disease->incidency = str_replace(",", ".", $disease->incidency);
                $property_crop_disease->risk = $disease->risk;
                $property_crop_disease->kml_file = isset($disease->kml_file) && $disease->kml_file ? $disease->kml_file : ($request->id ? $property_crop_disease->kml_file : null);
                // $property_crop_disease->kml_file = isset($disease->kml_file) && $disease->kml_file ? UploadFile($disease->kml_file, "uploads/property_crop_diseases/") : null;
                $kml_class = new KMLClass();
                $coordinates = $property_crop_disease->kml_file ? $kml_class->getCoordinates("property_crop_diseases/{$property_crop_disease->kml_file}") : null;

                if ($map_latitude && $map_longitude) {
                    $property_crop_disease->coordinates = new Point(floatval($map_latitude), floatval($map_longitude));
                } else {
                    $property_crop_disease->coordinates = $coordinates && count($coordinates) > 0 ? new Point(floatval($coordinates[0][0]), floatval($coordinates[0][1])) : null;
                }

                $property_crop_disease->save();

                $id_all[$key] = $property_crop_disease->id;

                $images = $request->hasFile('diseases_images') ? $request->file('diseases_images') : (isset($disease->images) ? $disease->images : null);

                if ($key == 0 && $images) {
                    $id_images[$key] = self::createImages($images, $property_crop_disease->id, 2, "property_crop_diseases");
                }
                if (!$disease->id) {
                    self::addNotification($property_crop_disease, 'disease');
                }
            }
            return true;
            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                'id_all' => $id_all,
                'id_images' => $id_images,
            ], 200);
        } catch (OperationException $e) {
            report($e);
            return false;
            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar/editar praga
    * @param admin_id
    * @param property_crop_join_id
    * @param pests
    * @return \Illuminate\Http\JsonResponse
    */
    public static function formPest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
                'pests' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar praga (monitoramento)', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $id_all = [];
            $id_images = [];

            $pestsLoop = gettype($request->pests) == 'string' ? json_decode($request->pests) : $request->pests;

            foreach ($pestsLoop as $key => $pest) {
                $pest = (object) $pest;

                if ($pest->id) {
                    $property_crop_pest = PropertyCropPest::find($pest->id);

                    if (!$property_crop_pest) {
                        throw new OperationException('Erro ao ler praga (monitoramento) na operação de edição', PropertyCropPest::getTableName(), "Praga não encontrada: {$pest->id}", 409);
                    }
                } else {
                    $property_crop_pest = new PropertyCropPest();
                }

                $map_latitude = isset($pest->latitude) ? $pest->latitude : null;
                $map_longitude = isset($pest->longitude) ? $pest->longitude : null;

                $property_crop_pest->admin_id = $request->admin_id;
                $property_crop_pest->open_date = $request->open_date ?? date('Y-m-d');
                $property_crop_pest->properties_crops_id = $request->property_crop_join_id;
                $property_crop_pest->interference_factors_item_id = $pest->interference_factors_item_id;
                $property_crop_pest->incidency = str_replace(",", ".", $pest->incidency);
                $property_crop_pest->quantity_per_meter = str_replace(",", ".", $pest->quantity_per_meter);
                $property_crop_pest->quantity_per_square_meter = str_replace(",", ".", $pest->quantity_per_square_meter);
                $property_crop_pest->risk = $pest->risk;
                $property_crop_pest->kml_file = isset($pest->kml_file) && $pest->kml_file ? UploadFile($pest->kml_file, "uploads/property_crop_pests/") : null;
                $kml_class = new KMLClass();
                $coordinates = $property_crop_pest->kml_file ? $kml_class->getCoordinates("property_crop_pests/{$property_crop_pest->kml_file}") : null;

                if ($map_latitude && $map_longitude) {
                    $property_crop_pest->coordinates = new Point(floatval($map_latitude), floatval($map_longitude));
                } else {
                    $property_crop_pest->coordinates = $coordinates && count($coordinates) > 0 ? new Point(floatval($coordinates[0][0]), floatval($coordinates[0][1])) : null;
                }

                $property_crop_pest->save();

                $id_all[$key] = $property_crop_pest->id;

                $images = $request->hasFile('pests_images') ? $request->file('pests_images') : (isset($pest->images) ? $pest->images : null);

                if ($key == 0 && $images) {
                    $id_images[$key] = self::createImages($images, $property_crop_pest->id, 3, "property_crop_pests");
                }
                if (!$pest->id) {
                    self::addNotification($property_crop_pest, 'pest');
                }
            }
            return true;
            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                'id_all' => $id_all,
                'id_images' => $id_images,
            ], 200);
        } catch (OperationException $e) {
            report($e);
            return false;
            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar/editar daninha
    * @param admin_id
    * @param property_crop_join_id
    * @param weeds
    * @return \Illuminate\Http\JsonResponse
    */

    public static function formWeed(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
                'weeds' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar daninha (monitoramento)', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $id_all = [];
            $id_images = [];

            $weedsLoop = gettype($request->weeds) == 'string' ? json_decode($request->weeds) : $request->weeds;

            foreach ($weedsLoop as $key => $weed) {
                $weed = (object) $weed;

                if ($weed->id) {
                    $property_crop_weed = PropertyCropWeed::find($weed->id);

                    if (!$property_crop_weed) {
                        throw new OperationException('Erro ao ler daninha (monitoramento) na operação de edição', PropertyCropWeed::getTableName(), "Daninha não encontrada: {$weed->id}", 409);
                    }
                } else {
                    $property_crop_weed = new PropertyCropWeed();
                }

                $map_latitude = isset($weed->latitude) ? $weed->latitude : null;
                $map_longitude = isset($weed->longitude) ? $weed->longitude : null;

                $property_crop_weed->admin_id = $request->admin_id;
                $property_crop_weed->open_date = $request->open_date ?? date('Y-m-d');
                $property_crop_weed->properties_crops_id = $request->property_crop_join_id;
                $property_crop_weed->interference_factors_item_id = $weed->interference_factors_item_id;
                $property_crop_weed->risk = $weed->risk;
                $property_crop_weed->kml_file = isset($weed->kml_file) && $weed->kml_file ? UploadFile($weed->kml_file, "uploads/property_crop_weeds/") : null;
                $kml_class = new KMLClass();
                $coordinates = $property_crop_weed->kml_file ? $kml_class->getCoordinates("property_crop_weeds/{$property_crop_weed->kml_file}") : null;

                if ($map_latitude && $map_longitude) {
                    $property_crop_weed->coordinates = new Point(floatval($map_latitude), floatval($map_longitude));
                } else {
                    $property_crop_weed->coordinates = $coordinates && count($coordinates) > 0 ? new Point(floatval($coordinates[0][0]), floatval($coordinates[0][1])) : null;
                }

                $property_crop_weed->save();

                $id_all[$key] = $property_crop_weed->id;

                $images = $request->hasFile('weeds_images') ? $request->file('weeds_images') : (isset($weed->images) ? $weed->images : null);

                if ($key == 0 && $images) {
                    $id_images[$key] = self::createImages($images, $property_crop_weed->id, 4, "property_crop_weeds");
                }
                if (!$weed->id) {
                    self::addNotification($property_crop_weed, 'weed');
                }
            }
            return true;
            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                'id_all' => $id_all,
                'id_images' => $id_images,
            ], 200);
        } catch (OperationException $e) {
            report($e);
            return false;
            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar/editar observação
    * @param admin_id
    * @param property_crop_join_id
    * @param observations
    * @return \Illuminate\Http\JsonResponse
    */

    public static function formObservations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'property_crop_join_id' => 'required',
                'observations' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar observação (monitoramento)', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $id_all = [];
            $id_images = [];

            $observationsLoop = gettype($request->observations) == 'string' ? json_decode($request->observations) : $request->observations;

            foreach ($observationsLoop as $key => $observation) {
                $observation = (object) $observation;

                if ($observation->id) {
                    $property_crop_observation = PropertyCropObservation::find($observation->id);

                    if (!$property_crop_observation) {
                        throw new OperationException('Erro ao ler observação (monitoramento) na operação de edição', PropertyCropObservation::getTableName(), "Observação não encontrado: {$observation->id}", 409);
                    }
                } else {
                    $property_crop_observation = new PropertyCropObservation();
                }

                $map_latitude = isset($observation->latitude) ? $observation->latitude : null;
                $map_longitude = isset($observation->longitude) ? $observation->longitude : null;

                $property_crop_observation->admin_id = $request->admin_id;
                $property_crop_observation->open_date = $request->open_date ?? date('Y-m-d');
                $property_crop_observation->properties_crops_id = $request->property_crop_join_id;
                $property_crop_observation->observations = $observation->observations;
                $property_crop_observation->risk = $observation->risk;
                $property_crop_observation->kml_file = isset($observation->kml_file) && $observation->kml_file ? UploadFile($observation->kml_file, "uploads/property_crop_observations/") : null;
                $kml_class = new KMLClass();
                $coordinates = $property_crop_observation->kml_file ? $kml_class->getCoordinates("property_crop_observations/{$property_crop_observation->kml_file}") : null;

                if ($map_latitude && $map_longitude) {
                    $property_crop_observation->coordinates = new Point(floatval($map_latitude), floatval($map_longitude));
                } else {
                    $property_crop_observation->coordinates = $coordinates && count($coordinates) > 0 ? new Point(floatval($coordinates[0][0]), floatval($coordinates[0][1])) : null;
                }

                $property_crop_observation->save();

                $id_all[$key] = $property_crop_observation->id;

                $images = $request->hasFile('observations_images') ? $request->file('observations_images') : (isset($observation->images) ? $observation->images : null);

                if ($key == 0 && $images) {
                    $id_images[$key] = self::createImages($images, $property_crop_observation->id, 5, "property_crop_observations");
                }

                if (!$observation->id) {
                    self::addNotification($property_crop_observation, 'observation');
                }
            }
            return true;
            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                'id_all' => $id_all,
                'id_images' => $id_images,
            ], 200);
        } catch (OperationException $e) {
            report($e);
            return false;
            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de cadastrar imagens
    */

    public static function createImages($images, $object_id, $type, $path)
    {
        $id_images = [];
        foreach ($images as $index => $image) {
            try {
                $property_crop_gallery = new PropertyCropGallery();
                $property_crop_gallery->object_id = $object_id;
                $property_crop_gallery->type = $type;
                $property_crop_gallery->image = gettype($image) == 'object' ? UploadFile($image, "/uploads/{$path}/") : $image;
                $property_crop_gallery->save();

                $id_images[$index] = $property_crop_gallery->id;
            } catch (OperationException $e) {
                report($e);
            }
        }

        return $id_images;
    }

    /*
    * função de deletar imagens
    * @param admin_id
    * @param date (data de exclusão de monitoramentos)
    * @param property_crop_join_id
    * @return \Illuminate\Http\JsonResponse
    */
    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'date' => 'required',
                'property_crop_join_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao deletar monitoramentos', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $date = date('Y-m-d', strtotime($request->date));

            if ($request->delete_stage) {
                PropertyCropStage::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['status' => 0]);
            }

            if ($request->delete_disease) {
                PropertyCropDisease::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['status' => 0]);
            }

            if ($request->delete_pest) {
                PropertyCropPest::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['status' => 0]);
            }

            if ($request->delete_weed) {
                PropertyCropWeed::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['status' => 0]);
            }

            if ($request->delete_observation) {
                PropertyCropObservation::where('properties_crops_id', $request->property_crop_join_id)->whereDate('open_date', $date)->update(['status' => 0]);
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de deletar imagens
    * @param admin_id
    * @param id
    * @return \Illuminate\Http\JsonResponse
    */
    public function deleteImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao deletar monitoramentos', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $image = PropertyCropGallery::find($request->id);
            $image->status = 0;
            $image->save();

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de listar monitoramentos
    * @param date
    */

    public function list($property_crop_join_id)
    {
        try {

            $management_data = Property::readManagementData($property_crop_join_id);

            // ordenar datas do mais recente para o mais antigo
            // $management_data = array_reverse($management_data);

            $join = PropertyCropJoin::with('crop')->find($property_crop_join_id);

            return response()->json([
                'status' => 200,
                'management_data' => $management_data,
                'crop' => $join ? $join->crop : null,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    /*
    * função de listar monitoramentos
    * @param date
    */

    public function read($property_crop_join_id, $date)
    {
        try {
            $stages = PropertyCropStage::with("images")->where('status', 1)->where('properties_crops_id', $property_crop_join_id)->whereDate('open_date', $date)->get();
            $diseases = PropertyCropDisease::with("images")->with('disease')->where('status', 1)->where('properties_crops_id', $property_crop_join_id)->whereDate('open_date', $date)->get();
            $pests = PropertyCropPest::with("images")->with('pest')->where('status', 1)->where('properties_crops_id', $property_crop_join_id)->whereDate('open_date', $date)->get();
            $weeds = PropertyCropWeed::with("images")->with('weed')->where('status', 1)->where('properties_crops_id', $property_crop_join_id)->whereDate('open_date', $date)->get();
            $observations = PropertyCropObservation::with("images")->where('status', 1)->where('properties_crops_id', $property_crop_join_id)->whereDate('open_date', $date)->get();

            $admin = null;

            if ($stages->first()) {
                $admin = $stages->first()->admin;
            } else if ($diseases->first()) {
                $admin = $diseases->first()->admin;
            } else if ($pests->first()) {
                $admin = $pests->first()->admin;
            } else if ($weeds->first()) {
                $admin = $weeds->first()->admin;
            } else if ($observations->first()) {
                $admin = $observations->first()->admin;
            }

            $join = PropertyCropJoin::find($property_crop_join_id);
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first();

            return response()->json([
                'status' => 200,
                'stages' => $stages,
                'diseases' => $diseases,
                'pests' => $pests,
                'weeds' => $weeds,
                'observations' => $observations,
                'property' => $join->property,
                'crop' => $join->crop,
                'harvest' => $join->harvest,
                'is_last_harvest' => $join->harvest_id == $last_harvest->id,
                'admin' => $admin,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao deletar monitoramentos', PropertyCropStage::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $date = date('Y-m-d', strtotime($request->date));

            if ($request->type == 'stage') {
                PropertyCropStage::find($request->id)->update(['status' => 0]);
            }

            if ($request->type == 'disease') {
                PropertyCropDisease::find($request->id)->update(['status' => 0]);
            }

            if ($request->type == 'pest') {
                PropertyCropPest::find($request->id)->update(['status' => 0]);
            }

            if ($request->type == 'weed') {
                PropertyCropWeed::find($request->id)->update(['status' => 0]);
            }

            if ($request->type == 'observation') {
                PropertyCropObservation::find($request->id)->update(['status' => 0]);
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }

    public static function addNotification($object, $type)
    {
        try {
            if ($type == 'stage') {
                $text_to_add = "Estádio";
            }

            if ($type == 'disease') {
                $text_to_add = "Doença";
            }

            if ($type == 'pest') {
                $text_to_add = "Praga";
            }

            if ($type == 'weed') {
                $text_to_add = "Daninha";
            }

            if ($type == 'observation') {
                $text_to_add = "Observação";
            }

            $title = "{$object->property_crop->property->name} - {$object->property_crop->crop->name} - {$text_to_add}";
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

                createNotification($title, $text, $object->risk, $admin->id, $object->property_crop->id);
            }

            createNotification($title, $text, $object->risk, $object->property_crop->property->admin->id, $object->property_crop->id);

            if (!$is_equal_admin) {
                createNotification($title, $text, $object->risk, $admin_section, $object->property_crop->id);
            }
        } catch (OperationException $e) {
            report($e);
        }
    }
}
