<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Exports\ApplicationExport;
use App\Exports\AssetExport;
use App\Exports\CropsExport;
use App\Exports\DataSeedsExport;
use App\Exports\DefensiveExport;
use App\Exports\DiseaseExport;
use App\Exports\InputsExport;
use App\Exports\MonitoringExport;
use App\Exports\MonitoringTableReportExport;
use App\Exports\PestsExport;
use App\Exports\ProductivityExport;
use App\Exports\RainGaugesDetailedExport;
use App\Exports\RainGaugesExport;
use App\Exports\ReportGeral;
use App\Exports\StockExport;
use App\Exports\WeedsExport;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateFileExportJob;
use App\Models\Admin;
use App\Models\Asset;
use App\Models\Crop;
use App\Models\Harvest;
use App\Models\InterferenceFactorItem;
use App\Models\Product;
use App\Models\Property;
use App\Models\PropertyCropDisease;
use App\Models\PropertyCropJoin;
use App\Models\PropertyCropObservation;
use App\Models\PropertyCropPest;
use App\Models\PropertyCropRainGauge;
use App\Models\PropertyCropStage;
use App\Models\PropertyCropWeed;
use App\Models\PropertyManagementDataHarvest;
use App\Models\PropertyManagementDataInput;
use App\Models\PropertyManagementDataSeed;
use App\Models\Stock;
use App\Models\StockExit;
use App\Models\StockIncoming;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public static function getType($type, Request $request)
    {
        $text = "";
        $properties = [];
        if ($request->get("properties_id")) {
            $properties = Property::select('id', 'name')->whereIn('id', explode(",", $request->get("properties_id")))->get();

            if (count($properties) > 0) {
                $text .= "-";

                foreach ($properties as $key => $property) {
                    $text .= friendlyUrl($property->name) . ($key < count($properties) - 1 ? "-&-" : "");
                }
            }

            $properties = $properties->pluck("name")->toArray();
        }

        if ($request->get("property_crop_join_id")) {
            $property_crop_join = PropertyCropJoin::with(['property' => function ($q) {
                $q->select('id', 'name');
            }])->find($request->get("property_crop_join_id"));

            if ($property_crop_join) {
                $text .= "- {$property_crop_join->property->name}";
            }
        }

        switch ($type) {
            case 'geral':
                return ['geral' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'pests':
                return ['pragas' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'weeds':
                return ['daninhas' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'diseases':
                return ['doencas' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'inputs':
                return ['insumos' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'data-seeds':
                return ['sementes' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'rain-gauges':
                return ['pluviometros' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'rain-gauges-detailed':
                return ['pluviometros' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'monitoring':
                return ['monitoramento' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'productivity':
                return ['produtividade' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'application':
                return ['aplicacoes' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'defensives':
                return ['defensivos' . '-' . date('d-m-Y') .  $text, $properties];
            case 'crops':
                return ['lavouras' . '-' . date('d-m-Y') .  $text, $properties];
            case 'assets':
                return ['bens' . '-' . date('d-m-Y') .  $text, $properties];
                break;
            case 'stocks':
                return ['estoques' . '-' . date('d-m-Y') .  $text, $properties];
                break;
        }
    }

    public function list($admin_id, $type, Request $request)
    {
        try {
            ini_set('memory_limit', '-1');
            $reports = collect([]);

            if (count($request->all())) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $export = $request->get('export');
            $file = '';
            $total_list = 0;
            $total_area = 0;
            $total_area_per_culture = 0;
            $total_area_per_culture_code = 0;
            $total_ha_per_culture = 0;
            $total_ha_per_culture_code = 0;
            $geral_infos = [];
            $properties_reports = [];

            $text_ha = $request->get("convert_to_alq") ? "alq" : "ha";
            $fileName = "";
            if ($export) {
                $pathRoot = $request->root();
                list($file_name_composition, $properties_reports) = self::getType($type, $request);

                if ($request->get('export_type') == 1) {
                    $fileName = 'relatorio-' . $file_name_composition   . '-' . bin2hex(random_bytes(3)) . '.xlsx';
                    $file = $pathRoot . '/uploads/spreadsheets/' . $fileName;
                } else {
                    $fileName = 'relatorio-' . $file_name_composition   . '-' . bin2hex(random_bytes(3)) . '.pdf';
                    $file = $pathRoot . '/uploads/pdf/' . $fileName;
                }
            }

            $is_different = false;

            switch ($type) {
                case 'geral':
                    list($reports, $total) = self::readGeralReport($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new ReportGeral($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.geral', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'pests':
                    list($reports, $total) = self::readMonitoringReport($admin_id, PropertyCropPest::class, 'pest', $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new PestsExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.pests', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'weeds':
                    list($reports, $total)  = self::readMonitoringReport($admin_id, PropertyCropWeed::class, 'weed', $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new WeedsExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.weeds', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'diseases':
                    list($reports, $total) = self::readMonitoringReport($admin_id, PropertyCropDisease::class, 'disease', $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new DiseaseExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.diseases', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'inputs':
                    list($reports, $total) = self::readInputsReport($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new InputsExport($reports, $request->get("visualization_type") ?? 1), $fileName, 'public');
                        } else {
                            // job de exportação
                            // GenerateFileExportJob::dispatch($fileName, $reports->toJson(), 'inputs');
                            $admin = Admin::find($admin_id);
                            $pdf = Pdf::loadView('pdf.inputs', ['currency' => $admin->currency_id, 'reports' => $reports, 'properties_reports' => $properties_reports, 'visualization_type' => $request->get("visualization_type")]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'data-seeds':
                    list($reports, $total) = self::readDataSeeds($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new DataSeedsExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.data_seeds', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'rain-gauges':
                    list($reports, $total) = self::readRainGauges($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new RainGaugesExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.rain_gauges', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'rain-gauges-detailed':
                    list($reports, $rain_gauges_infos) = self::readRainGaugesDetailed($request);

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new RainGaugesDetailedExport($reports, $rain_gauges_infos), $fileName, 'public');
                        } else {
                            // $pdf = Pdf::loadView('pdf.rain_gauges', ['reports' => $reports]);
                            // $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'monitoring':
                    // dd("oi");
                    list($reports, $total) = self::readMonitoringTableReport($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new MonitoringExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.monitoring', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'productivity':
                    list($reports, $total) = self::readProductivity($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new ProductivityExport($reports, $text_ha), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.productivity', ['reports' => $reports, 'text_ha' => $text_ha]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'productivity-graph':
                    list($reports, $total) = self::readProductivityGraph($admin_id, $request);
                    $total_list = $total;

                    break;
                case 'application':
                    list($reports, $total) = self::readApplication($admin_id, $request, $page, $export);
                    $total_list = $total;

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new ApplicationExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.application', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'cultures':
                    list($reports, $total, $total_area, $total_area_per_culture, $total_area_per_culture_code, $total_ha_per_culture, $total_ha_per_culture_code, $geral_infos, $is_different) = self::readCultures($admin_id, $request);
                    $total_list = $total;
                    break;

                case 'defensives':
                    $reports = self::readDefensives($admin_id, $request, $page, $export);

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new DefensiveExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.defensives', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'crops':
                    $reports = self::readCrops($admin_id, $request, $page, $export);

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new CropsExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.crops', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'assets':
                    $reports = self::readAssets($admin_id, $request, $page, $export);

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new AssetExport($reports), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.assets', ['reports' => $reports]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
                case 'stocks':
                    $tab = $request->get("tab");
                    $reports = self::readStocksReport($admin_id, $request, $page, $export, $tab);

                    if ($export) {
                        if ($request->get('export_type') == 1) {
                            Excel::store(new StockExport($reports, $tab), $fileName, 'public');
                        } else {
                            $pdf = Pdf::loadView('pdf.stocks', ['reports' => $reports, 'tab' => $tab]);
                            $pdf->save(public_path('uploads/pdf/' . $fileName));
                        }
                    }
                    break;
            }

            if ($export && $page) {
                $skip = ($page - 1) * 20;
                $reports = $reports->slice($skip)->take(20);
            }

            return response()->json([
                'status' => 200,
                'reports' => $reports,
                'total' => $total_list,
                'file_dump' => $file,
                'file_name' => $fileName,
                'total_area' => $total_area,
                'total_area_per_culture' => $total_area_per_culture,
                'total_area_per_culture_code' => $total_area_per_culture_code,
                'total_ha_per_culture' => $total_ha_per_culture,
                'total_ha_per_culture_code' => $total_ha_per_culture_code,
                'geral_infos' => $geral_infos,
                'is_different' => $is_different,
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

    // ler itens dos selects dos formulários
    public function getOptions($admin_id, Request $request)
    {
        try {
            list($properties, $total) = Property::readProperties($admin_id, null, null, ['id', 'name']);
            list($crops, $total) = Crop::readCrops($admin_id, null, null, ['id', 'name', 'property_id', 'area']);
            list($harvests, $total) = Harvest::readHarvests(null, null, ['id', 'name']);
            list($cultures, $total) = Product::readProducts($admin_id, null, null, 1, ['id', 'name', 'extra_column', 'type']);

            // $data_seeds = PropertyManagementDataSeed::whereHas('property_crop', function ($q) use ($properties) {
            //     $q->whereIn('property_id', $properties->pluck('id')->toArray());
            // })
            //     ->with(['property_crop' => function($q){
            //         $q->select('id', 'property_id', 'crop_id', 'harvest_id');
            //     }, 'product' => function ($q) {
            //         $q->select('id', 'name', 'type', 'object_type');
            //     }])
            //     ->where("status", 1)
            //     ->get();

            $weeds = [];
            $diseases = [];
            $pests = [];
            $products = [];


            if ($request->get("with") == "weeds") {
                $weeds = InterferenceFactorItem::select("id", 'name', 'type', 'status')->where("status", 1)->where('type', 1)->orderBy('name', 'asc')->get();
            }

            if ($request->get("with") == "diseases") {
                $diseases = InterferenceFactorItem::select("id", 'name', 'type', 'status')->where("status", 1)->where('type', 2)->orderBy('name', 'asc')->get();

                $cultures->each(function ($culture) {
                    $culture->diseases = $culture->diseases->where('status', 1)->sortBy('name');
                });
            }

            if ($request->get("with") == "pests") {
                $pests = InterferenceFactorItem::select("id", 'name', 'type', 'status')->where("status", 1)->where('type', 3)->orderBy('name', 'asc')->get();

                $cultures->each(function ($culture) {
                    $culture->pests = $culture->pests->where('status', 1)->sortBy('name');
                });
            }

            if ($request->get("with") == "products") {
                $products = Product::select('id', 'name', 'type', 'extra_column', 'object_type')->where("status", 1)->orderBy('name', 'asc')->get();
            }

            return response()->json([
                'status' => 200,
                'properties' => $properties,
                'crops' => $crops,
                'harvests' => $harvests,
                'cultures' => $cultures,
                // 'data_seeds' => $data_seeds,
                'weeds' => $weeds,
                'pests' => $pests,
                'products' => $products,
                'diseases' => $diseases,
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

    // função para ler estrutura geral do report
    public static function readJoins($admin_id, $request, $type = "", $page = null, $export = false, $filterCallback = null)
    {
        list($properties, $total_properties) = Property::readProperties($admin_id, null, null);

        $joins = PropertyCropJoin::select('properties_crops_join.id', 'properties_crops_join.property_id', 'properties_crops_join.crop_id', 'properties_crops_join.harvest_id', 'properties_crops_join.status', 'properties_crops_join.is_subharvest', 'properties_crops_join.subharvest_name')->whereIn('property_id', $properties->pluck('id')->toArray())
            ->join('properties', 'properties.id', '=', 'properties_crops_join.property_id')
            ->where("properties_crops_join.status", 1)
            ->whereHas('property')
            ->whereHas('crop')
            ->whereHas('harvest')
            ->with(['property' => function ($q) {
                $q->select("id", "name", 'status');
            }, 'crop' => function ($q) {
                $q->select("id", 'area', "name", 'property_id', 'status');
            }, 'harvest' => function ($q) {
                $q->select("id", "name", 'is_last_harvest', 'status');
            }]);

        // if ($type == "geral") {
        //     $joins = $joins->leftJoin('properties_management_data_seeds', 'properties_management_data_seeds.properties_crops_id', '=', 'properties_crops_join.id')->leftJoin('products', 'products.id', '=', 'properties_management_data_seeds.product_id')->orderBy('products.name', 'desc')->orderBy('properties_management_data_seeds.product_variant', 'asc');
        // } else {
        $joins = $joins->orderBy('properties.name', 'asc');
        // }


        if ($request->get("property_crop_join_id")) {
            $joins = $joins->where('properties_crops_join.id', $request->get("property_crop_join_id"));
        }


        if ($request->get("properties_id")) {
            $joins = $joins->whereIn('property_id', explode(",", $request->get("properties_id")));
        }

        if ($request->get("crops_id")) {
            $joins = $joins->whereIn('crop_id', explode(",", $request->get("crops_id")));
        }

        if ($request->get("property_crop_join_id")) {
            $join = PropertyCropJoin::find($request->get("property_crop_join_id"));

            if ($join) {
                $joins = $joins->where('harvest_id', $join->harvest_id);
            }
        } else if ($request->get("harvests_id")) {
            $joins = $joins->whereIn('harvest_id', explode(",", $request->get("harvests_id")));
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $joins = $joins->where('harvest_id', $last_harvest->id);
        }

        if ($request->get("hide_subharvest")) {
            $joins = $joins->where('is_subharvest', 0);
        }
        // lavouras que ja foram colhidas sao escondidas
        if ($request->get("search_harvested") == '0') {
            $joins = $joins->whereDoesntHave('data_harvest', function ($q) {
                $q->where('status', 1);
            });
        }

        if ($request->get("culture_id")) {
            $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                $q->where('product_id', $request->get("culture_id"));

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        if ($request->get("dap_begin")) {
            $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                $q->where('date', '>=', $request->get("dap_begin"));
            });
        }

        if ($request->get("dap_end")) {
            $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                $q->where('date', '<=', $request->get("dap_end"));
            });
        }

        if ($request->get("dae_begin")) {
            $joins = $joins->whereHas('data_population', function ($q) use ($request) {
                $q->where('emergency_percentage_date', '>=', $request->get("dae_begin"));
            });
        }

        if ($request->get("dae_end")) {
            $joins = $joins->whereHas('data_population', function ($q) use ($request) {
                $q->where('emergency_percentage_date', '<=', $request->get("dae_end"));
            });
        }

        if ($request->get("daa_begin")) {
            if ($type != "application") {
                $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                    $q->where('date', '>=', $request->get("daa_begin"))->where('type', 2);
                });
            }
        }

        if ($request->get("daa_end")) {
            if ($type != "application") {
                $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                    $q->where('date', '<=', $request->get("daa_end"))->where('type', 2);
                });
            }
        }

        if ($request->get("products_id")) {
            if ($request->get("product_type") == 1) {
                $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                    $q->whereIn('product_id', explode(",", $request->get("products_id")));
                });
            } else {
                if ($request->get("product_type") == 3) {
                    $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                        $q->whereIn('product_id', explode(",", $request->get("products_id")));
                    });
                } else {
                    $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                        $q->whereIn('product_id', explode(",", $request->get("products_id")))->whereHas('product', function ($q) use ($request) {
                            $q->where('object_type', floatval($request->get("product_type")) - 3);
                        });
                    });
                }
            }
        }

        if ($request->get("product_type") && !$request->get("products_id")) {
            if ($request->get("product_type") == 1) {
                $joins = $joins->whereHas('data_seed');
            } else {
                if ($request->get("product_type") == 3) {
                    $joins = $joins->whereHas('data_input', function ($q) {
                        $q->whereHas('product', function ($q) {
                            $q->where('type', 3);
                        });
                    });
                } else {
                    $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                        $q->where('type', 2)->whereHas('product', function ($q) use ($request) {
                            $q->where('object_type', floatval($request->get("product_type")) - 3);
                        });
                    });
                }
            }
        }

        if ($request->get("date_begin")) {
            if ($request->get("product_type")) {
                if ($request->get("product_type") == 1) {
                    $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                        $q->where('date', '>=', $request->get("date_begin"));
                    });
                } else {
                    $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                        $q->where('date', '>=', $request->get("date_begin"))->where('type', 2);
                    });
                }
            }

            if ($type == "application") {
                $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                    $q->where('date', '>=', $request->get("date_begin"));
                });
            }

            if ($type == 'rain_gauge') {
                $joins = $joins->whereHas('rain_gauge', function ($q) use ($request) {
                    $q->where('date', '>=', $request->get("date_begin"));
                });
            }
        }

        if ($request->get("date_end")) {
            if ($request->get("product_type")) {
                if ($request->get("product_type") == 1) {
                    $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                        $q->where('date', '<=', $request->get("date_end"));
                    });
                } else {
                    $joins = $joins->whereHas('data_input', function ($q) use ($request) {
                        $q->where('date', '<=', $request->get("date_end"))->where('type', 2);
                    });
                }
            }

            if ($type == "application") {
                $joins = $joins->whereHas('data_seed', function ($q) use ($request) {
                    $q->where('date', '<=', $request->get("date_end"));
                });
            }

            if ($type == 'rain_gauge') {
                $joins = $joins->whereHas('rain_gauge', function ($q) use ($request) {
                    $q->where('date', '<=', $request->get("date_end"));
                });
            }
        }

        if ($request->get("min_production")) {
            $joins = $joins->whereHas('data_harvest', function ($q) use ($request) {
                $q->where('total_production', '>=', isString($request->get("min_production")));
            });
        }

        if ($request->get("max_production")) {
            $joins = $joins->whereHas('data_harvest', function ($q) use ($request) {
                $q->where('total_production', '<=', isString($request->get("max_production")));
            });
        }
        if ($filterCallback !== null && is_callable($filterCallback)) {
            $joins = $joins->where($filterCallback);
        }

        // Paginação
        $total = $joins->count();

        if ($page && !$export) {
            $skip = ($page - 1) * 20;
            $joins = $joins->skip($skip)->take(20)->get();
        } else {
            $joins = $joins->get();
        }

        return [$joins, $total];
    }

    // report geral
    public static function readGeralReport($admin_id, $request, $page = null, $export = false)
    {
        $filterFunction = function ($q) {
            $q->where(function ($query) {
                $query->whereHas('data_seed')->orWhereHas('data_input')->orWhereHas("stage");
            });
        };

        list($joins, $total) = self::readJoins($admin_id, $request, "geral", $page, $export, $filterFunction);

        // $joins = $joins->filter(function ($crop_item) {
        //     return $crop_item->data_input->count() > 0 || $crop_item->data_seed->count() > 0;
        // })->values();

        $joins->map(function ($crop_item) {
            // $crop_item->property_name = $crop_item->property->name;
            // $crop_item->property = $crop_item->property;
            // $crop_item->crop = $crop_item->crop;

            // $crop_item->culture_table = $crop_item->data_seed->first() && $crop_item->data_seed->first()->product ? $crop_item->data_seed->first()->product->name : '--';

            if ($crop_item->data_seed->first()) {
                // primeiro lemos todos os data_seeds do join
                $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $crop_item->id)->where('status', 1)->get();

                // pegamos as culturas dos data_seeds
                $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                $crop_item->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                $crop_item->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                $crop_item->culture_table = rtrim($crop_item->culture_table, ",<br>");
                $crop_item->culture_code_table = rtrim($crop_item->culture_code_table, ",<br>");
            } else {
                $crop_item->culture_table = '--';
                $crop_item->culture_code_table = '--';
            }

            // $crop_item->culture_code_table = $crop_item->data_seed->first() && $crop_item->data_seed->first()->product ? $crop_item->data_seed->first()->product_variant : '--';

            $now = $crop_item->data_harvest->first() ? Carbon::createFromFormat('Y-m-d', $crop_item->data_harvest->first()->date) : Carbon::now();
            // $now = Carbon::now();

            if ($crop_item->data_population->first()) {

                $date = Carbon::createFromFormat('Y-m-d', $crop_item->data_population->sortByDesc('emergency_percentage_date')->first()->emergency_percentage_date);
                $crop_item->emergency_table =  $date->diffInDays($now);
            } else {
                $crop_item->emergency_table =  '--';
            }

            if ($crop_item->data_seed->first()) {
                $date = Carbon::createFromFormat('Y-m-d', $crop_item->data_seed->sortByDesc('date')->first()->date);
                $crop_item->plant_table =  $date->diffInDays($now);
            } else {
                $crop_item->plant_table =  '--';
            }

            if ($crop_item->data_input->where('type', 2)->first()) {
                $date = Carbon::createFromFormat('Y-m-d', $crop_item->data_input->where('type', 2)->sortByDesc('date')->first()->date);
                $crop_item->application_table =  $date->diffInDays($now);
            } else {
                $crop_item->application_table =  '--';
            }

            $crop_item->productivity = $crop_item->data_harvest->first() ? $crop_item->data_harvest->first()->productivity : '--';
            $crop_item->total_production = $crop_item->data_harvest->first() ? $crop_item->data_harvest->first()->total_production : '--';

            if ($crop_item->stage->first()) {
                $crop_item->stage_table = getStageText($crop_item->stage->sortByDesc('open_date')->first());
            } else {
                $crop_item->stage_table = '--';
            }

            unset($crop_item->data_seed);
            unset($crop_item->data_population);
            unset($crop_item->data_input);
            unset($crop_item->data_harvest);
            unset($crop_item->stage);
        });

        return [$joins->sortBy([
            ['property.name', 'asc'],
            ['culture_table', 'desc'],
            // ['culture_code_table', 'asc'],
            ['plant_table', 'desc'],
            ['application_table', 'desc'],

        ])->values(), $total];
    }

    // report de monitoramentos 
    public static function readMonitoringReport($admin_id, $class, $item_with, $request, $page = null, $export = false)
    {
        list($properties, $total_count) = Property::readProperties($admin_id, null, null);

        $table_name = $class::getTableName();

        $select_raw = "{$table_name}.id, {$table_name}.properties_crops_id, {$table_name}.interference_factors_item_id, {$table_name}.status, {$table_name}.admin_id, {$table_name}.open_date, {$table_name}.risk";

        if ($table_name == "properties_crops_pests") {
            $select_raw .= ", {$table_name}.incidency, {$table_name}.quantity_per_meter, {$table_name}.quantity_per_square_meter";
        } else if ($table_name == "properties_crops_diseases") {
            $select_raw .= ", {$table_name}.incidency";
        }

        $reports = $class::selectRaw($select_raw)->whereHas('property_crop', function ($query) use ($properties, $request) {
            $query->whereIn('property_id', $properties->pluck('id')->toArray())->whereHas('property')->whereHas('crop')->whereHas('harvest');

            if ($request->get("hide_subharvest")) {
                $query = $query->where('is_subharvest', 0);
            }

            if ($request->get("search_harvested") == '0') {
                $query = $query->whereDoesntHave('data_harvest', function ($q) {
                    $q->where('status', 1);
                });
            }
        })
            ->join('properties_crops_join', 'properties_crops_join.id', '=', "{$table_name}.properties_crops_id")
            ->join('properties', 'properties.id', '=', 'properties_crops_join.property_id')
            ->with(['property_crop' => function ($q) {
                $q->select('id', 'property_id', 'crop_id', 'harvest_id', 'is_subharvest', 'subharvest_name')->with(['property' => function ($q) {
                    $q->select('id', 'name');
                }, 'harvest' => function ($q) {
                    $q->select('id', 'name');
                }, 'crop' => function ($q) {
                    $q->select('id', 'name', 'area');
                }, 'data_seed' => function ($q) {
                    $q->select('id', 'date', 'product_id', 'product_variant', 'properties_crops_id')->orderBy('date', 'desc');
                }]);
            }, $item_with => function ($q) use ($item_with) {
                $q->select('id', 'name', 'status', 'observation');
            }, 'admin' => function ($q) {
                $q->select('id', 'name');
            }])
            ->where("properties_crops_join.status", 1)
            ->where("{$table_name}.status", 1)
            ->orderBy('properties.name', 'asc');

        if ($request->get("properties_id")) {
            $reports = $reports->whereIn('properties_crops_join.property_id', explode(",", $request->get("properties_id")));
        }



        if ($request->get("crops_id")) {
            $reports = $reports->whereIn('properties_crops_join.crop_id', explode(",", $request->get("crops_id")));
        }

        if ($request->get("harvests_id")) {
            $reports = $reports->whereIn('properties_crops_join.harvest_id', explode(",", $request->get("harvests_id")));
        } else if ($request->get("property_crop_join_id")) {
            $join = PropertyCropJoin::find($request->get("property_crop_join_id"));

            if ($join) {
                $reports = $reports->where('properties_crops_join.harvest_id', $join->harvest_id);
            }
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $reports = $reports->where('properties_crops_join.harvest_id', $last_harvest->id);
        }

        if ($request->get("culture_id")) {
            $reports = $reports->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('product_id', $request->get("culture_id"));

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        if ($request->get("weeds_id")) {
            $reports = $reports->whereHas('weed', function ($q) use ($request) {
                $q->whereIn('id', explode(",", $request->get("weeds_id")));
            });
        }

        if ($request->get("diseases_id")) {
            $reports = $reports->whereHas('disease', function ($q) use ($request) {
                $q->whereIn('id', explode(",", $request->get("diseases_id")));
            });
        }

        if ($request->get("pests_id")) {
            $reports = $reports->whereHas('pest', function ($q) use ($request) {
                $q->whereIn('id', explode(",", $request->get("pests_id")));
            });
        }

        if ($request->get("dap_begin")) {
            $reports = $reports->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '>=', $request->get("dap_begin"));
            });
        }

        if ($request->get("dap_end")) {
            $reports = $reports->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '<=', $request->get("dap_end"));
            });
        }

        if ($request->get("open_date_begin")) {
            $reports = $reports->where('open_date', '>=', $request->get("open_date_begin"));
        }

        if ($request->get("open_date_end")) {
            $reports = $reports->where('open_date', '<=', $request->get("open_date_end"));
        }

        if ($request->get("risk")) {
            $reports = $reports->where('risk', $request->get("risk"));
        }

        if ($table_name != "properties_crops_weeds") {
            if ($request->get("quantity_per_meter_min")) {
                $reports = $reports->where('quantity_per_meter', '>=', isString($request->get("quantity_per_meter_min")));
            }
            if ($request->get("quantity_per_meter_max")) {
                $reports = $reports->where('quantity_per_meter', '<=', isString($request->get("quantity_per_meter_max")));
            }

            if ($request->get("quantity_per_square_meter_min")) {
                $reports = $reports->where('quantity_per_square_meter', '>=', isString($request->get("quantity_per_square_meter_min")));
            }
            if ($request->get("quantity_per_square_meter_max")) {
                $reports = $reports->where('quantity_per_square_meter', '<=', isString($request->get("quantity_per_square_meter_max")));
            }

            if ($request->get("incidency_min")) {
                $reports = $reports->where('incidency', '>=', isString($request->get("incidency_min")));
            }

            if ($request->get("incidency_max")) {
                $reports = $reports->where('incidency', '<=', isString($request->get("incidency_max")));
            }
        }


        // Paginação
        $total = $reports->count();

        if ($page && !$export) {
            $skip = ($page - 1) * 20;
            $reports = $reports->skip($skip)->take(20)->get();
        } else {
            $reports = $reports->get();
        }

        $reports->map(function ($item) {
            if ($item->property_crop->data_seed->first()) {
                // primeiro lemos todos os data_seeds do join
                $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $item->property_crop->id)->where('status', 1)->get();

                // pegamos as culturas dos data_seeds
                $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                $item->property_crop->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                $item->property_crop->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                $item->property_crop->culture_table = rtrim($item->property_crop->culture_table, ",<br>");
                $item->property_crop->culture_code_table = rtrim($item->property_crop->culture_code_table, ",<br>");
            } else {
                $item->property_crop->culture_table = '--';
                $item->property_crop->culture_code_table = '--';
            }

            // $item->property_crop->culture_table = $item->property_crop->data_seed->first() && $item->property_crop->data_seed->first()->product ? $item->property_crop->data_seed->first()->product->name : '--';

            // $item->property_crop->culture_code_table = $item->property_crop->data_seed->first() && $item->property_crop->data_seed->first()->product ? $item->property_crop->data_seed->first()->product_variant : '--';

            if ($item->property_crop->stage->last()) {
                $item->property_crop->stage_table = getStageText($item->property_crop->stage->sortByDesc('open_date')->first());
            } else {
                $item->property_crop->stage_table = '--';
            }

            // unset($item->property_crop->data_seed);
            unset($item->property_crop->stage);
        });

        return [$reports, $total];
    }

    // insumos
    public static function readInputsReport($admin_id, $request, $page = null, $export = false)
    {
        $filterFunction = function ($q) {
            $q->where(function ($query) {
                $query->whereHas('data_seed')->orWhereHas('data_input')->orWhereHas("stage");
            });
        };

        list($joins, $total) = self::readJoins($admin_id, $request, "inputs", $page, $export, $filterFunction);

        if ($request->get("visualization_type") != 3) {

            $joins->map(function ($crop_item, $index) use ($request) {

                $crop_item->data_input = $crop_item->data_input()->select('id', 'product_id', 'date', 'type', 'dosage', 'properties_crops_id', 'status')->with(['product' => function ($q) {
                    $q->select("id", "name", "type", "object_type")->without(['diseases', 'pests']);
                }])->get();

                $crop_item->data_seed = $crop_item->data_seed()->select('id', 'product_id', 'product_variant', 'date', 'quantity_per_ha', 'kilogram_per_ha', 'properties_crops_id', 'status')->with(['product' => function ($q) {
                    $q->select("id", "name", "type", "object_type")->without(['diseases', 'pests']);
                }])->get();


                if ($crop_item->data_seed->first()) {
                    // primeiro lemos todos os data_seeds do join
                    $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $crop_item->id)->where('status', 1)->get();

                    // pegamos as culturas dos data_seeds
                    $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                    $crop_item->culture_table = join(", ", $cultures->pluck('name')->toArray());

                    $crop_item->culture_table = rtrim($crop_item->culture_table, ", ");
                } else {
                    $crop_item->culture_table = '--';
                }

                if (!$request->get('visualization_type') || $request->get('visualization_type') == 1) {
                    // removendo data_input e data_seed
                    $crop_item->merged_data_input = $crop_item->data_input->merge($crop_item->data_seed);
                } else {
                    $data_seed = $crop_item->data_seed()->selectRaw('id, status, product_id, sum(kilogram_per_ha) as kilogram_per_ha')->with(['product' => function ($q) {
                        $q->select('id', 'name');
                    }])->where('status', 1)->groupBy('product_id')->get();

                    $data_input = $crop_item->data_input()->selectRaw('id, status, product_id, type, sum(dosage) as dosage')->with(['product' => function ($q) {
                        $q->select('id', 'name', 'type', 'object_type');
                    }])->where('status', 1)->groupBy('product_id')->get();

                    // dd($data_seed);

                    $crop_item->merged_data_input = $data_seed->merge($data_input);
                }

                $merged_data_array = $crop_item->merged_data_input->toArray();
                // Usando usort com uma função de comparação customizada
                usort($merged_data_array, function ($a, $b) use ($request) {
                    if (!$request->get('visualization_type') || $request->get('visualization_type') == 1) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    } else {
                        return strtotime($b[isset($b['type'])  ? 'dosage' : 'kilogram_per_ha']) - strtotime($a[isset($a['type'])  ? 'dosage' : 'kilogram_per_ha']);
                    }
                });

                // Convertendo de volta para a coleção
                $crop_item->merged_data_input = collect($merged_data_array);

                if ($request->get('products_id')) {
                    $crop_item->merged_data_input = $crop_item->merged_data_input->whereIn('product_id', explode(",", $request->get("products_id")));
                }

                if ($request->get('product_type')) {
                    // se for 1, remove todos os itens merged_data_input que possuem a coluna type
                    // se for 3, traz todos os itens que o type for 1
                    // se for outro, traz todos os itens que o object type for igual o product_type

                    if ($request->get('product_type') == 1) {
                        // remove todos os itens merged_data_input que possuem a coluna type
                        $crop_item->merged_data_input = $crop_item->merged_data_input->where('type', null);
                    } else {
                        if ($request->get('product_type') == 3) {
                            $crop_item->merged_data_input = $crop_item->merged_data_input->where('type', 1);
                        } else {
                            $crop_item->merged_data_input = $crop_item->merged_data_input->where('product.object_type', floatval($request->get('product_type')) - 3);
                        }
                    }
                }

                $crop_item->sum_dosages = $crop_item->merged_data_input->sum('dosage') + $crop_item->merged_data_input->sum('kilogram_per_ha');
                unset($crop_item->data_input);
                unset($crop_item->data_seed);
            });
        } else {
            // transformando em array e deixando só os uniques
            $properties_ids = $joins->pluck("property_id");
            $properties_ids = array_unique($properties_ids->toArray());
            $new_joins = collect([]);

            foreach ($properties_ids as $property_id) {
                $property = Property::find($property_id);

                $property->stock_exits = StockExit::whereHas('stock', function ($q) use ($property_id) {
                    $q->where('property_id', $property_id);
                })->with('stock')->get();

                $property->stock_incomings = $property->stock_incomings()->with('stock')->get();

                $harvests = Harvest::where('status', 1)->whereIn("id", $joins->where('property_id', $property_id)->pluck("harvest_id"))->get();

                $crops = Crop::where("status", 1)->whereIn('id', PropertyCropJoin::select('crop_id')->whereHas('data_seed')->where('status', 1)->where('property_id', $property_id)->whereIn('harvest_id', $harvests->pluck('id')))->get();


                $property->crop_area = $crops->sum('area');
                $property->harvest = join(", ", $harvests->pluck('name')->toArray());

                $data_seeds = PropertyManagementDataSeed::select('id', 'product_id', 'properties_crops_id', 'status')->whereIn('properties_crops_id', $joins->where('property_id', $property_id)->pluck('id')->toArray())->where('status', 1)->get();

                // dd($data_seeds);

                if ($data_seeds->first()) {
                    // pegamos as culturas dos data_seeds
                    $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                    $property->culture_table = join(", ", $cultures->pluck('name')->toArray());

                    $property->culture_table = rtrim($property->culture_table, ", ");
                } else {
                    $property->culture_table = '--';
                }

                $data_seed_query = PropertyManagementDataSeed::whereIn('properties_crops_id', $joins->where('property_id', $property_id)->pluck('id'))->with(['product' => function ($q) {
                    $q->select('id', 'name');
                }])->where('status', 1);

                $data_input_query = PropertyManagementDataInput::whereIn('properties_crops_id', $joins->where('property_id', $property_id)->pluck('id'))->with(['product' => function ($q) {
                    $q->select('id', 'name', 'type', 'object_type');
                }])->where('status', 1);

                $data_seeds = (clone $data_seed_query)->selectRaw('id, status, product_id, sum(kilogram_per_ha) as kilogram_per_ha, properties_crops_id')->groupBy('product_id')->get();
                $data_seeds_not_grouped = (clone $data_seed_query)->get();

                $data_input = (clone $data_input_query)->selectRaw('id, status, product_id, type, sum(dosage) as dosage, properties_crops_id')->groupBy('product_id')->get();
                $data_input_not_grouped = (clone $data_input_query)->with("property_crop.crop")->get();

                // dd($data_input_not_grouped->where("product_id", 7920));

                $property->merged_data_input = $data_seeds->merge($data_input);

                if ($request->get("products_id")) {
                    $property->merged_data_input = $property->merged_data_input->whereIn('product_id', explode(",", $request->get("products_id")));
                }

                if ($request->get('product_type')) {
                    // se for 1, remove todos os itens merged_data_input que possuem a coluna type
                    // se for 3, traz todos os itens que o type for 1
                    // se for outro, traz todos os itens que o object type for igual o product_type

                    if ($request->get('product_type') == 1) {
                        // remove todos os itens merged_data_input que possuem a coluna type
                        $property->merged_data_input = $property->merged_data_input->where('type', null);
                    } else {
                        if ($request->get('product_type') == 3) {
                            $property->merged_data_input = $property->merged_data_input->where('type', 1);
                        } else {
                            $property->merged_data_input = $property->merged_data_input->where('product.object_type', floatval($request->get('product_type')) - 3);
                        }
                    }
                }

                $property->total_products = 0;

                $property->merged_data_input->map(function ($item) use (&$property, $data_input_not_grouped, $data_seeds_not_grouped) {
                    if (isset($item->type)) {
                        $item->total_dosage = 0;

                        $data_input_not_grouped->where('product_id', $item->product_id)->each(function ($input) use (&$item) {

                            if ($input->type == 1 || ($input->type == 2 && $input->product->product_type == $item->product->product_type)) {
                                $item->total_dosage += $input->dosage * $input->property_crop->crop->area;
                            }
                        });
                    } else {
                        $item->total_dosage = 0;

                        $data_seeds_not_grouped->where('product_id', $item->product_id)->each(function ($seed) use (&$item) {
                            $item->total_dosage += $seed->kilogram_per_ha * $seed->property_crop->crop->area;
                        });
                    }
                    $item->dosage = $item->total_dosage / $property->crop_area;
                    $item->total_dosage = $item->total_dosage;

                    // $property->dosage = $item->total_dosage / $property->crop_area;
                    $property->total_products += $item->total_dosage;
                });

                $merged_data_array = $property->merged_data_input->toArray();
                // Usando usort com uma função de comparação customizada
                usort($merged_data_array, function ($a, $b) use ($request) {
                    if (!$request->get('visualization_type') || $request->get('visualization_type') == 1) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    } else {
                        return strtotime($b[isset($b['type'])  ? 'dosage' : 'kilogram_per_ha']) - strtotime($a[isset($a['type'])  ? 'dosage' : 'kilogram_per_ha']);
                    }
                });

                // Convertendo de volta para a coleção
                $property->merged_data_input = collect($merged_data_array);





                $property->sum_dosages = $property->merged_data_input->sum('dosage') + $property->merged_data_input->sum('kilogram_per_ha');

                $new_joins->push($property);
            }

            $joins = $new_joins;
        }

        // dd($joins);
        return [$joins, $total];
    }

    // sementes
    public static function readDataSeeds($admin_id, $request, $page = null, $export = false)
    {
        list($properties, $total_count) = Property::readProperties($admin_id, null, null);

        $data_seeds = PropertyManagementDataSeed::select("properties_management_data_seeds.*")->whereHas('property_crop', function ($q) use ($properties, $request) {
            $q->whereIn('property_id', $properties->pluck('id')->toArray())->whereHas('property')->whereHas('crop')->whereHas('harvest');

            if ($request->get("hide_subharvest")) {
                $q = $q->where('is_subharvest', 0);
            }

            if ($request->get("search_harvested") == '0') {
                $q = $q->whereDoesntHave('data_harvest', function ($q) {
                    $q->where('status', 1);
                });
            }
        })
            // ->whereHas('data_population')
            ->join('properties_crops_join', 'properties_crops_join.id', '=', "properties_management_data_seeds.properties_crops_id")
            ->join('properties', 'properties.id', '=', 'properties_crops_join.property_id')
            ->with(['property_crop' => function ($q) {
                $q->with(['property' => function ($q) {
                    $q->select('id', 'name');
                }, 'harvest' => function ($q) {
                    $q->select('id', 'name');
                }, 'crop' => function ($q) {
                    $q->select('id', 'name', 'area');
                }]);
            }, 'data_population' => function ($q) {
                $q->select("id", "properties_crops_id", "seed_per_linear_meter", "seed_per_square_meter", "quantity_per_ha", "emergency_percentage", "emergency_percentage_date", "property_management_data_seed_id", "plants_per_hectare")->orderBy('id', 'desc');
            }, 'product' => function ($q) {
                $q->select('id', 'name', 'type', 'object_type')->without(['diseases', 'pests']);
            }])
            ->where("properties_crops_join.status", 1)
            ->where("properties_management_data_seeds.status", 1)
            // ->groupBy('properties_crops_join.crop_id')
            ->orderBy('properties.name', 'asc');

        if ($request->get("properties_id")) {
            $data_seeds = $data_seeds->whereIn('properties_crops_join.property_id', explode(",", $request->get("properties_id")));
        }

        if ($request->get("crops_id")) {
            $data_seeds = $data_seeds->whereIn('properties_crops_join.crop_id', explode(",", $request->get("crops_id")));
        }

        if ($request->get("harvests_id")) {
            $data_seeds = $data_seeds->whereIn('properties_crops_join.harvest_id', explode(",", $request->get("harvests_id")));
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $data_seeds = $data_seeds->where('properties_crops_join.harvest_id', $last_harvest->id);
        }

        if ($request->get("culture_id")) {
            $data_seeds = $data_seeds->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('product_id', $request->get("culture_id"));

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        if ($request->get("dap_begin")) {
            $data_seeds = $data_seeds->where('date', '>=', $request->get("dap_begin"));
        }

        if ($request->get("dap_end")) {
            $data_seeds = $data_seeds->where('date', '<=', $request->get("dap_begin"));
        }

        if ($request->get("min_population")) {
            $data_seeds = $data_seeds->whereHas('data_population', function ($q) use ($request) {
                $q->where('quantity_per_ha', '>=', isString($request->get("min_population")));
            });
        }

        if ($request->get("max_population")) {
            $data_seeds = $data_seeds->whereHas('data_population', function ($q) use ($request) {
                $q->where('quantity_per_ha', '<=', isString($request->get("max_population")));
            });
        }

        if ($request->get("min_emergency")) {
            $data_seeds = $data_seeds->whereHas('data_population', function ($q) use ($request) {
                $q->where('emergency_percentage', '>=', isString($request->get("min_emergency")));
            });
        }

        if ($request->get("max_emergency")) {
            $data_seeds = $data_seeds->whereHas('data_population', function ($q) use ($request) {
                $q->where('emergency_percentage', '<=', isString($request->get("max_emergency")));
            });
        }

        // Paginação
        $data_seeds = $data_seeds->get();
        $total = $data_seeds->count();

        if ($page && !$export) {
            $skip = ($page - 1) * 20;
            $data_seeds = $data_seeds->slice($skip)->take(20)->values();
        }

        return [$data_seeds, $total];
    }

    // pluviometros
    public static function readRainGauges($admin_id, $request, $page = null, $export = false)
    {
        $filterFunction = function ($q) {
            $q->where(function ($query) {
                $query->whereHas('rain_gauge');
            });
        };

        list($joins, $total) = self::readJoins($admin_id, $request, "rain_gauge", $page, $export, $filterFunction);

        // $joins = $joins->filter(function ($crop_item) {
        //     return $crop_item->rain_gauge->count() > 0;
        // })->values();

        $joins->map(function ($join) {

            if ($join->data_seed->first()) {
                // primeiro lemos todos os data_seeds do join
                $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $join->id)->where('status', 1)->get();

                // pegamos as culturas dos data_seeds
                $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                $join->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                $join->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                $join->culture_table = rtrim($join->culture_table, ",<br>");
                $join->culture_code_table = rtrim($join->culture_code_table, ",<br>");
            } else {
                $join->culture_table = '--';
                $join->culture_code_table = '--';
            }


            try {
                $rainGaugeDate = $join->rain_gauge->sortBy('date')->first();
                if ($rainGaugeDate) {
                    $date = Carbon::createFromFormat("Y-m-d", $rainGaugeDate->date);

                    $last_plant_rain_gauges = $date->format('Y-m-d') != date('Y-m-d') ? $date : Carbon::now()->subDays(90);
                } else {
                    throw new \Exception("No rain gauge data available.");
                }
            } catch (\Exception $e) {
                // Fallback para data_seed ou subtrai 90 dias da data atual se a data de rain_gauge for inválida ou estiver fora do range
                $dataSeedDate = $join->data_seed->sortBy('date')->first();
                if ($dataSeedDate) {
                    try {
                        $last_plant_rain_gauges = Carbon::createFromFormat("Y-m-d", $dataSeedDate->date);
                    } catch (\Exception $e) {
                        // Se a data de data_seed for inválida, usa a data atual subtraindo 90 dias
                        $last_plant_rain_gauges = Carbon::now()->subDays(90);
                    }
                } else {
                    // Se não houver data_seed, usa a data atual subtraindo 90 dias
                    $last_plant_rain_gauges = Carbon::now()->subDays(90);
                }
            }


            // if ($join->rain_gauge->sortBy('date')->first()) {
            //     $last_plant_rain_gauges =  $join->rain_gauge->sortBy('date')->first()->date != date('Y-m-d') ? (new \DateTime($join->rain_gauge->sortBy('date')->first()->date))->format('Y-m-d') : Carbon::now()->subDays(90);
            // } else {
            //     $last_plant_rain_gauges =  $join->data_seed->sortBy('date')->first() ? $join->data_seed->sortBy('date')->first()->date : Carbon::now()->subDays(90);
            // }

            $rain_gauges = $join->rain_gauge()->whereDate('date', '>=', $last_plant_rain_gauges);
            $end_plant_rain_gauges = null;

            if ($join->data_harvest->sortBy('date')->first()) {
                $end_plant_rain_gauges = $join->data_harvest->sortBy('date')->first()->date;

                $rain_gauges = $rain_gauges->whereDate('date', '<=', $end_plant_rain_gauges);
            }

            $rain_gauges = $rain_gauges->get();

            list($rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume) = \App\Http\Controllers\Api\V1\PropertyController::getRainGauges('custom', $rain_gauges, $last_plant_rain_gauges, $end_plant_rain_gauges);

            $join->rain_gauge_infos = $rain_gauge_infos;

            unset($join->rain_gauge);
            unset($join->data_seed);
        });

        return [$joins, $total];
    }

    public function readRainGaugesDetailed(Request $request)
    {

        $data_rain_gauges = PropertyCropRainGauge::with('property_crop')
            ->where('status', 1)
            ->where('properties_crops_id', $request->property_crop_join_id)
            ->where('date', '>=', $request->date_begin)
            ->where('date', '<=', $request->date_end)
            ->get();

        list($rain_gauge_infos, $rain_gauges_graph, $rain_gauge_total_volume) = PropertyController::getRainGauges('custom', $data_rain_gauges, $request->date_begin, $request->date_end);

        $data_rain_gauges->map(function ($item) {
            $item->date = (new \DateTime($item->date))->format('d/m/Y');
        });

        return [$data_rain_gauges, $rain_gauge_infos];
    }

    // tabela de monitoramentos
    public static function readMonitoringTableReport($admin_id, $request, $page = null, $export = false)
    {
        $filterFunction = function ($q) {
            $q->where(function ($query) {
                $query->whereHas('stage')->orWhereHas('diseases')->orWhereHas("pests")->orWhereHas('weeds')->orWhereHas('observations');
            });
        };

        list($joins, $total) = self::readJoins($admin_id, $request, "", $page, $export, $filterFunction);

        // $joins = $joins->filter(function ($crop_item) {
        //     return  $crop_item->stage->count() > 0 || $crop_item->diseases->count() > 0 || $crop_item->pests->count() > 0 || $crop_item->weeds->count() > 0;
        // })->values();

        $joins->map(function ($join) use ($request) {


            if ($join->data_seed->first()) {
                // primeiro lemos todos os data_seeds do join
                $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $join->id)->where('status', 1)->get();

                // pegamos as culturas dos data_seeds
                $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                $join->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                $join->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                $join->culture_table = rtrim($join->culture_table, ",<br>");
                $join->culture_code_table = rtrim($join->culture_code_table, ",<br>");
            } else {
                $join->culture_table = '--';
                $join->culture_code_table = '--';
            }

            $stages = PropertyCropStage::select('id', 'vegetative_age_value', 'reprodutive_age_value', 'vegetative_age_text', 'reprodutive_age_text', 'risk', 'properties_crops_id', 'open_date')->where('status', 1)
                ->where('properties_crops_id', $join->id)
                ->with('images')
                ->orderBy('id', 'desc');

            $diseases = PropertyCropDisease::select('id', 'interference_factors_item_id', 'incidency', 'risk', 'properties_crops_id', 'open_date')->where('status', 1)
                ->with(['disease' => function ($q) {
                    $q->select("id", "name", "status");
                }])
                ->where('properties_crops_id', $join->id)
                ->orderBy('id', 'desc');

            $pests = PropertyCropPest::select('id', 'interference_factors_item_id', 'incidency', 'quantity_per_meter', 'quantity_per_square_meter', 'risk', 'properties_crops_id', 'open_date')->where('status', 1)
                ->with(['pest' => function ($q) {
                    $q->select("id", "name", "status");
                }, 'images'])
                ->where('properties_crops_id', $join->id)
                ->orderBy('id', 'desc');

            // $pests = $pests->get()
            //     ->groupBy(function ($item) {
            //         return (new \DateTime($item->open_date))->format('d-m-Y');
            //     });
            $weeds = PropertyCropWeed::select('id', 'interference_factors_item_id', 'risk', 'properties_crops_id', 'open_date')->where('status', 1)
                ->with(['weed' => function ($q) {
                    $q->select("id", "name", "status");
                }, 'images'])
                ->where('properties_crops_id', $join->id)
                ->orderBy('id', 'desc');

            $observations = PropertyCropObservation::select('id', 'observations', 'risk', 'properties_crops_id', 'open_date')->where('status', 1)
                ->where('properties_crops_id', $join->id)
                ->with(['admin', 'images'])
                ->orderBy('id', 'desc');

            if ($request->get("date_begin")) {
                $stages = $stages->where('open_date', '>=', $request->get("date_begin"));
                $diseases = $diseases->where('open_date', '>=', $request->get("date_begin"));
                $pests = $pests->where('open_date', '>=', $request->get("date_begin"));
                $weeds = $weeds->where('open_date', '>=', $request->get("date_begin"));
                $observations = $observations->where('open_date', '>=', $request->get("date_begin"));
            }

            if ($request->get("date_end")) {
                $stages = $stages->where('open_date', '<=', $request->get("date_end"));
                $diseases = $diseases->where('open_date', '<=', $request->get("date_end"));
                $pests = $pests->where('open_date', '<=', $request->get("date_end"));
                $weeds = $weeds->where('open_date', '<=', $request->get("date_end"));
                $observations = $observations->where('open_date', '<=', $request->get("date_end"));
            }

            if ($request->get("risk")) {
                $stages = $stages->where('risk', $request->get("risk"));
                $diseases = $diseases->where('risk', $request->get("risk"));
                $pests = $pests->where('risk', $request->get("risk"));
                $weeds = $weeds->where('risk', $request->get("risk"));
                $observations = $observations->where('risk', $request->get("risk"));
            }

            $stages = $stages->get()
                ->groupBy(function ($item) {
                    return (new \DateTime($item->open_date))->format('d-m-Y');
                });

            $diseases = $diseases->get()
                ->groupBy(function ($item) {
                    return (new \DateTime($item->open_date))->format('d-m-Y');
                });

            $weeds = $weeds->get()
                ->groupBy(function ($item) {
                    return (new \DateTime($item->open_date))->format('d-m-Y');
                });

            $pests = $pests->get()
                ->groupBy(function ($item) {
                    return (new \DateTime($item->open_date))->format('d-m-Y');
                });

            $observations = $observations->get()
                ->groupBy(function ($item) {
                    return (new \DateTime($item->open_date))->format('d-m-Y');
                });

            $dates = $stages->keys()->merge($diseases->keys())
                ->merge($pests->keys())
                ->merge($weeds->keys())
                ->merge($observations->keys())
                ->unique()
                ->sort();

            $management_data = [];

            // Converte as chaves de string para DateTime
            $dates = $dates->map(function ($date) {
                return new \DateTime($date);
            });

            // Ordena as datas em ordem decrescente
            $dates = $dates->sort(function ($a, $b) {
                return $b <=> $a;
            });

            // Converte as datas de volta para string se necessário
            $dates = $dates->map(function ($date) {
                return $date->format('d-m-Y');
            });

            foreach ($dates as $date) {
                $management_data[$date] = [
                    'stages' => $stages->get($date) ?: [],
                    'diseases' => $diseases->get($date) ?: [],
                    'pests' => $pests->get($date) ?: [],
                    'weeds' => $weeds->get($date) ?: [],
                    'observations' => $observations->get($date) ?: [],
                ];
            }

            $join->management_data = $management_data;

            unset($join->data_seed);
            unset($join->stage);
        });

        $joins = $joins->filter(function ($join) {
            return count($join->management_data) > 0;
        })->values();

        return [$joins, $total];
    }

    // produtividade
    public static function readProductivity($admin_id, $request, $page = null, $export = false)
    {
        list($properties, $total_count) = Property::readProperties($admin_id, null, null);

        $data_harvests = PropertyManagementDataHarvest::select("properties_management_data_harvest.*")->whereHas('property_crop', function ($q) {
            $q->whereHas('property')->whereHas('crop');
        })
            ->join('properties_crops_join', 'properties_crops_join.id', '=', "properties_management_data_harvest.properties_crops_id")
            ->join('properties', 'properties_crops_join.property_id', '=', "properties.id")
            ->where("properties_crops_join.status", 1)
            ->where("properties_management_data_harvest.total_production", '>', 0)
            ->where("properties_management_data_harvest.status", 1)
            ->whereIn('properties_crops_join.property_id', $properties->pluck('id')->toArray())
            ->with(['property_crop' => function ($q) {
                $q->with(['property' => function ($q) {
                    $q->select('id', 'name');
                }, 'harvest' => function ($q) {
                    $q->select('id', 'name');
                }, 'crop' => function ($q) {
                    $q->select('id', 'name', 'area');
                }]);
            }, 'data_seed'])
            ->orderBy('properties.name', 'asc');

        // filtro de propriedades, lavouras, safras, culturas, cultivares, dap inicial, dap final, produtividade minima e maxima
        if ($request->get("properties_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.property_id', explode(",", $request->get("properties_id")));
        }

        if ($request->get("crops_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.crop_id', explode(",", $request->get("crops_id")));
        }

        if ($request->get("harvests_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.harvest_id', explode(",", $request->get("harvests_id")));
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $data_harvests = $data_harvests->where('properties_crops_join.harvest_id', $last_harvest->id);
        }

        if ($request->get("culture_id")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('product_id', $request->get("culture_id"));

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        if ($request->get("dap_begin")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '>=', $request->get("dap_begin"));
            });
        }

        if ($request->get("dap_end")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '<=', $request->get("dap_end"));
            });
        }

        if ($request->get("min_production")) {
            $data_harvests = $data_harvests->where('total_production', '>=', isString($request->get("min_production")));
        }

        if ($request->get("max_production")) {
            $data_harvests = $data_harvests->where('total_production', '<=', isString($request->get("max_production")));
        }

        $total = $data_harvests->count();
        if ($page && !$export) {
            $skip = ($page - 1) * 20;
            $data_harvests = $data_harvests->skip($skip)->take(20)->get();
        } else {
            $data_harvests = $data_harvests->get();
        }

        $data_harvests->each(function ($harvest) {
            if ($harvest->data_seed) {
                $harvest->culture_table = $harvest->data_seed->product->name;
                $harvest->culture_code_table = $harvest->data_seed->product_variant;
                $harvest->date_plant = Carbon::createFromFormat('Y-m-d', $harvest->data_seed->date)->format('d/m/Y');
            } else {
                if ($harvest->property_crop->data_seed->first()) {
                    // primeiro lemos todos os data_seeds do join
                    $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $harvest->property_crop->id)->where('status', 1)->get();

                    // pegamos as culturas dos data_seeds
                    $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                    $harvest->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                    $harvest->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                    $harvest->culture_table = rtrim($harvest->culture_table, ",<br>");
                    $harvest->culture_code_table = rtrim($harvest->culture_code_table, ",<br>");

                    $harvest->date_plant = Carbon::createFromFormat('Y-m-d', $harvest->property_crop->data_seed->first()->date)->format('d/m/Y');
                } else {
                    $harvest->culture_table = '--';
                    $harvest->culture_code_table = '--';
                    $harvest->date_plant = '--';
                }
            }

            $harvest->productivity_per_hectare = number_format($harvest->productivity / 60, 1, ',', '.');
            $harvest->total_production_per_hectare = number_format($harvest->total_production / 60, 1, ',', '.');
        });

        return [$data_harvests->sortBy([
            ['property_crop.property.name', 'asc'],
            ['culture_table', 'asc'],
            ['culture_code_table', 'asc'],
        ])->values(), $total];
    }

    public function readProductivityGraph($admin_id, $request)
    {
        list($properties, $total_properties) = Property::readProperties($admin_id, null, null);


        $data_harvests = PropertyManagementDataHarvest::select("properties_management_data_harvest.*")->whereHas('property_crop', function ($q) use ($request) {
            $q->whereHas('property')->whereHas('crop')->whereHas('harvest')->whereHas("data_seed");

            if ($request->get("search_harvested") == '0') {
                $q = $q->whereDoesntHave('data_harvest', function ($q) {
                    $q->where('status', 1);
                });
            }
        })
            ->join('properties_crops_join', 'properties_crops_join.id', '=', "properties_management_data_harvest.properties_crops_id")
            ->where("properties_management_data_harvest.total_production", '>', 0)
            ->where("properties_crops_join.status", 1)
            ->where("properties_management_data_harvest.status", 1)
            ->whereIn('properties_crops_join.property_id', $properties->pluck('id')->toArray());

        if ($request->get("properties_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.property_id', explode(",", $request->get("properties_id")));
        }

        if ($request->get("crops_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.crop_id', explode(",", $request->get("crops_id")));
        }

        if ($request->get("harvests_id")) {
            $data_harvests = $data_harvests->whereIn('properties_crops_join.harvest_id', explode(",", $request->get("harvests_id")));
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $data_harvests = $data_harvests->where('properties_crops_join.harvest_id', $last_harvest->id);
        }

        if ($request->get("hide_subharvest")) {
            $data_harvests = $data_harvests->where('properties_crops_join.is_subharvest', 0);
        }

        if ($request->get("culture_id")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('product_id', $request->get("culture_id"));

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        if ($request->get("dap_begin")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '>=', $request->get("dap_begin"));
            });
        }

        if ($request->get("dap_end")) {
            $data_harvests = $data_harvests->whereHas('property_crop.data_seed', function ($q) use ($request) {
                $q->where('date', '<=', $request->get("dap_end"));
            });
        }
        if ($request->get("min_production")) {
            $data_harvests = $data_harvests->where('total_production', '>=', isString($request->get("min_production")));
        }

        if ($request->get("max_production")) {
            $data_harvests = $data_harvests->where('total_production', '<=', isString($request->get("max_production")));
        }

        $data_harvests = $data_harvests->get();

        $productivities_count = [
            'cultures' => [],
        ];

        $data_harvests->map(function ($harvest) use (&$productivities_count, $request) {

            if ($harvest->data_seed) {
                $data_seeds = $request->get("culture_id") ? $harvest->property_crop->data_seed->where('product_id', $request->get("culture_id"))->where('id', $harvest->property_management_data_seed_id) : $harvest->property_crop->data_seed->where('id', $harvest->property_management_data_seed_id);
            } else {
                $data_seeds = $request->get("culture_id") ? $harvest->property_crop->data_seed->where('product_id', $request->get("culture_id")) : $harvest->property_crop->data_seed;
            }

            // echo "##########<br>";
            // echo "<br>Propriedade: {$harvest->property_crop->property->name}<br>";
            // echo "Lavoura: {$harvest->property_crop->crop->name}<br>";
            // echo "Área total: {$harvest->property_crop->crop->area}<br>";
            // echo "Produção total: {$harvest->total_production}<br>";
            // echo "Produtividade: {$harvest->productivity}<br><br>";

            $summed_operations = false;

            foreach ($data_seeds as $data_seed) {

                if (!isset($productivities_count['cultures'][$data_seed->product_id])) {
                    $productivities_count['cultures'][$data_seed->product_id] = [
                        'total_area' => 0,
                        'total_production' => 0,
                        'harvest' => '',
                    ];
                }

                if (!isset($productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant])) {
                    $productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant] = [
                        'total_area' => 0,
                        'total_production' => 0,
                        'harvest' => '',
                        'product_id' => Product::find($data_seed->product_id)->name
                    ];
                }

                // echo "Cultura: {$data_seed->product_variant}<br>";
                // echo "Área: {$data_seed->area}<br>";
                // echo "Produção: " . ($data_seed->area * $harvest->total_production) / $harvest->property_crop->crop->area . "<br>";

                if (!$summed_operations) {
                    $productivities_count['cultures'][$data_seed->product_id]['total_area'] += $harvest->property_crop->crop->area;
                    $productivities_count['cultures'][$data_seed->product_id]['total_production'] += $harvest->total_production;
                    $summed_operations = true;
                }
                $productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant]['total_area'] += $data_seed->area;
                // $productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant]['total_production'] += $harvest->productivity * $data_seed->area;

                $productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant]['total_production'] += ($data_seed->area * $harvest->total_production) / $harvest->property_crop->crop->area;

                $productivities_count['cultures'][$data_seed->product_id]['harvest'] = $harvest->property_crop->harvest->name;
                $productivities_count['cultures'][$data_seed->product_id]['codes'][$data_seed->product_variant]['harvest'] = $harvest->property_crop->harvest->name;
            }
            // echo "##########<br><br>";
        });

        // die;

        // gerando ton/ha de acordo com a produção total e total de area
        foreach ($productivities_count['cultures'] as $key => $value) {
            $product = Product::find($key);
            $name = $product->name . ' ' . $productivities_count['cultures'][$key]['harvest'];

            // $total_area = 0;
            // $total_production = 0;

            $productivities_count['cultures'][$name] = $productivities_count['cultures'][$key];

            // percorrendo culture_codes
            foreach ($value['codes'] as $code_key => $code_value) {
                $productivity_per_hectare = $code_value['total_production'] / $code_value['total_area'];
                // $productivity_per_hectare = $code_value['total_production'];

                $productivities_count['cultures'][$name]['codes'][$code_key]['productivity_per_hectare'] = $productivity_per_hectare;

                $productivities_count['cultures'][$name]['codes'][$code_key]['productivity_per_hectare_sc']  = $productivity_per_hectare / 60;

                // $total_area += $code_value['total_area'];
                // $total_production += $code_value['total_production'];
            }

            // $value['total_area'] = $total_area;
            // $value['total_production'] = $total_production;

            // dd($value);

            // $productivity_per_hectare = $value['total_production'];
            $productivity_per_hectare = $value['total_area'] > 0 ? $value['total_production'] / $value['total_area'] : 0;

            $productivities_count['cultures'][$name]['productivity_per_hectare'] = $productivity_per_hectare;
            $productivities_count['cultures'][$name]['productivity_per_hectare_sc'] = $productivity_per_hectare / 60;


            unset($productivities_count['cultures'][$key]);
        }

        // ordenando de forma descendente por productivity_per_hectare sem perder o index da array que é o nome do produto
        uasort($productivities_count['cultures'], function ($a, $b) {
            return $b['productivity_per_hectare'] <=> $a['productivity_per_hectare'];
        });

        // ordenando de forma descendente os codes pelo productivity_per_hectare sem perder o index da array que é o nome do produto
        foreach ($productivities_count['cultures'] as $key => $value) {
            uasort($productivities_count['cultures'][$key]['codes'], function ($a, $b) {
                return $b['productivity_per_hectare'] <=> $a['productivity_per_hectare'];
            });
        }


        //  substituindo o id pelo nome na chave $productivities_count['cultures']



        return [$productivities_count, $data_harvests->count()];
    }

    public static function readApplication($admin_id, $request, $page = null, $export = false)
    {
        $filterFunction = function ($q) {
            $q->where(function ($query) {
                $query->whereHas('data_input', function ($q) {
                    $q->where('type', 2)->whereHas('product', function ($q) {
                        $q->where('object_type', 4)->where('is_for_seeds', 0);
                    });
                })->whereHas('data_seed');
            });
        };

        list($joins, $total) = self::readJoins($admin_id, $request, "application", $page, $export, $filterFunction);

        // $joins = $joins->filter(function ($crop_item) {
        //     $crop_item->data_input = $crop_item->data_input()->whereHas('product', function ($q) {
        //         $q->where('object_type', 4);
        //     })->get();
        //     return $crop_item->data_input->where('type', 2)->count() > 0 && $crop_item->data_seed->count() > 0;
        // })->values();

        $joins->map(function ($join) {
            if ($join->data_seed->first()) {
                // primeiro lemos todos os data_seeds do join
                $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $join->id)->where('status', 1)->get();

                // pegamos as culturas dos data_seeds
                $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
                $join->culture_table = join(",<br>", $cultures->pluck('name')->toArray());
                $join->culture_code_table = join(",<br>", $data_seeds->pluck("product_variant")->toArray());

                $join->culture_table = rtrim($join->culture_table, ",<br>");
                $join->culture_code_table = rtrim($join->culture_code_table, ",<br>");
            } else {
                $join->culture_table = '--';
                $join->culture_code_table = '--';
            }

            $date_plant = $join->data_seed->first()  ? $join->data_seed->first()->date : null;

            $join->date_plant = $date_plant ? Carbon::createFromFormat('Y-m-d', $date_plant)->format('d/m/Y') : '--';

            // $now = Carbon::now();
            $now = $join->data_harvest->first() ? Carbon::createFromFormat('Y-m-d', $join->data_harvest->first()->date) : Carbon::now();

            if ($join->data_population->first()) {

                $date = Carbon::createFromFormat('Y-m-d', $join->data_population->sortByDesc('emergency_percentage_date')->first()->emergency_percentage_date);
                $join->emergency_table =  $date->diffInDays($now);
            } else {
                $join->emergency_table =  '--';
            }

            if ($join->data_seed->first()) {
                $date = Carbon::createFromFormat('Y-m-d', $join->data_seed->sortByDesc('date')->first()->date);
                $join->plant_table =  $date->diffInDays($now);
            } else {
                $join->plant_table =  '--';
            }


            if ($join->data_input->where('type', 2)->where('product.object_type', 4)->where('product.is_for_seeds', 0)->where('date', '>', $date_plant)->first()) {
                $group = $join->data_input->where('type', 2)->where('product.object_type', 4)->groupBy("date")->sortBy(function ($item, $key) {
                    return $key;
                });
                $date = Carbon::createFromFormat('Y-m-d', $join->data_input->where('type', 2)->where('product.object_type', 4)->where('product.is_for_seeds', 0)->where('date', '>', $date_plant)->sortByDesc('date')->first()->date);
                $date_first = Carbon::createFromFormat('Y-m-d', $join->data_input->where('type', 2)->where('product.object_type', 4)->where('product.is_for_seeds', 0)->where('date', '>', $date_plant)->sortBy('date')->first()->date);
                $join->application_number = array_search($date->format("Y-m-d"), array_keys($group->toArray())) + 1;

                $join->application_table =  $date->diffInDays($now);
                $join->application_date_table = $date_first->format('d/m/Y');

                if ($date_plant) {
                    $join->days_between_plant_and_last_application =  $date->diffInDays(Carbon::createFromFormat('Y-m-d', $date_plant));
                    $join->days_between_plant_and_first_application =  $date_first->diffInDays(Carbon::createFromFormat('Y-m-d', $date_plant));
                } else {
                    $join->days_between_plant_and_last_application = '--';
                    $join->days_between_plant_and_first_application = '--';
                }
            } else {
                $join->application_table =  '--';
                $join->application_date_table = '--';
                $join->days_between_plant_and_last_application = '--';
                $join->days_between_plant_and_first_application = '--';
                $join->application_number = '--';
            }

            if ($join->stage->last()) {
                $join->stage_table = getStageText($join->stage->sortByDesc('open_date')->first());
            } else {
                $join->stage_table = '--';
            }

            unset($join->data_input);
            unset($join->data_seed);
            unset($join->data_population);
            unset($join->stage);
        });

        if ($request->get("depua_begin")) {
            $joins = $joins->where("days_between_plant_and_last_application", ">=", intval($request->get("depua_begin")));
        }

        if ($request->get("depua_end")) {
            $joins = $joins->where("days_between_plant_and_last_application", "<=", intval($request->get("depua_end")));
        }


        if ($request->get("daa_begin")) {
            $joins = $joins->where("application_table", ">=", intval($request->get("daa_begin")));
        }

        if ($request->get("daa_end")) {
            $joins = $joins->where("application_table", "<=", intval($request->get("daa_end")));
        }

        if ($request->get("depua_begin") || $request->get("depua_end") || $request->get("daa_begin") || $request->get("daa_end")) {
            $joins = $joins->values();
            $total = $joins->count();
        }

        return [$joins, $total];
    }

    public function readCultures($admin_id, $request)
    {
        list($properties, $total_count) = Property::readProperties($admin_id, null, null);

        checkSection($admin_id);

        // foreach (PropertyManagementDataSeed::whereHas('property_crop', function ($q) {
        //     $q->whereHas('property')->whereHas('crop')->whereHas('harvest');
        // })->where("area", null)->get() as $item) {
        //     // alocando area da lavoura respectiva
        //     $item->area = !$item->area || $item->area == 0 ? $item->property_crop->crop->area : $item->area;
        //     $item->save();
        // }

        $data_seeds = PropertyManagementDataSeed::select("properties_management_data_seeds.id", "properties_management_data_seeds.properties_crops_id", "properties_management_data_seeds.area", "properties_management_data_seeds.product_id", "properties_management_data_seeds.product_variant")->whereHas('property_crop', function ($q) use ($properties, $request) {
            $q->whereIn('property_id', $properties->pluck('id')->toArray())->whereHas('property')->whereHas('crop')->whereHas('harvest')->where('status', 1);

            if ($request->get("hide_subharvest")) {
                $q = $q->where('is_subharvest', 0);
            }

            if ($request->get("search_harvested") == '0') {
                $q = $q->whereDoesntHave('data_harvest', function ($q) {
                    $q->where('status', 1);
                });
            }
        })
            ->where("properties_management_data_seeds.status", 1)
            ->where("properties_management_data_seeds.area", "!=", null);

        // filtros
        if ($request->get("properties_id")) {
            $data_seeds = $data_seeds->whereHas('property_crop', function ($q) use ($request) {
                $q->whereIn('property_id', explode(",", $request->get("properties_id")));
            });
        }

        if ($request->get("crops_id")) {
            $data_seeds = $data_seeds->whereHas('property_crop', function ($q) use ($request) {
                $q->whereIn('crop_id', explode(",", $request->get("crops_id")));
            });
        }

        if ($request->get("harvests_id")) {
            $data_seeds = $data_seeds->whereHas('property_crop', function ($q) use ($request) {
                $q->whereIn('harvest_id', explode(",", $request->get("harvests_id")));
            });
        } else {
            // lendo ultima safra e só puxar registros dela
            $last_harvest = Harvest::where('status', 1)->where('is_last_harvest', 1)->first() ?? Harvest::where('status', 1)->orderBy('id', 'desc')->first();
            $data_seeds = $data_seeds->whereHas('property_crop', function ($q) use ($last_harvest) {
                $q->where('harvest_id', $last_harvest->id);
            });
        }

        $culture_name = "Todas as culturas";

        if ($request->get("culture_id")) {
            $data_seeds = $data_seeds->whereHas('product', function ($q) use ($request, &$culture_name) {
                $q->where('id', $request->get("culture_id"));

                $culture_name = Product::find($request->get("culture_id"))->name;

                if ($request->get("culture_code")) {
                    $q->where('product_variant', $request->get("culture_code"));
                }
            });
        }

        // Paginação
        $data_seeds = $data_seeds->get();

        //  calculando área total das lavouras
        $count_crops = [];
        $total_area = 0;
        $total_area_crops = 0;
        $summed_crop = [];

        // foreach ($data_seeds as $data_seed) {
        //     $crop = $data_seed->property_crop->crop;

        //     if (!in_array($crop->id, $count_crops)) {
        //         $total_area += $crop->area;
        //         array_push($count_crops, $crop->id);
        //     }
        // }

        // calculando % da área utilizada pelas culturas

        $total_area_per_culture = [];
        $total_area_per_culture_code = [];
        $total_ha_per_culture = [];
        $total_ha_per_culture_code = [];

        // $properties_name = "";
        // $harvests_name = "";
        // $crops_name = "";

        $data_seeds->each(function ($item) use (&$total_area, &$total_area_per_culture, &$total_area_per_culture_code, &$total_ha_per_culture, &$total_ha_per_culture_code, &$total_area_crops, &$summed_crop) {
            $total_area += $item->area;
            if (!in_array($item->property_crop->crop->id, $summed_crop)) {
                $total_area_crops += $item->property_crop->crop->area;
            }
            array_push($summed_crop, $item->property_crop->crop->id);

            $item->area_percentage = number_format(($item->area / $total_area) * 100, 2);

            if (!isset($total_area_per_culture[$item->product_id])) {
                $total_area_per_culture[$item->product_id] = 0;
                $total_ha_per_culture[$item->product_id] = 0;
            }

            if (!isset($total_area_per_culture_code[$item->product_variant])) {
                $total_area_per_culture_code[$item->product_variant] = 0;
                $total_ha_per_culture_code[$item->product_variant] = 0;
            }


            $total_area_per_culture[$item->product_id] += $item->area;
            $total_area_per_culture_code[$item->product_variant] += $item->area;

            $total_ha_per_culture[$item->product_id] += $item->area;
            $total_ha_per_culture_code[$item->product_variant] += $item->area;


            unset($item->data_seed);
            unset($item->property_crop);
        });



        if (!$request->get("properties_id")) {
            $properties_name = "Todas as propriedades";
        } else {
            $properties_name = Property::whereIn('id', explode(",", $request->get("properties_id")))->pluck('name')->join(", ");
        }

        if (!$request->get("crops_id")) {
            $crops_name = "Todas as lavouras";
        } else {
            $crops_name = Crop::whereIn('id', explode(",", $request->get("crops_id")))->pluck('name')->join(", ");
        }

        if (!$request->get("harvests_id")) {
            $harvests_name = $last_harvest->name;
        } else {
            $harvests_name = Harvest::whereIn('id', explode(",", $request->get("harvests_id")))->pluck('name')->join(", ");
        }

        // calculando % da área utilizada pelas culturas
        foreach ($total_area_per_culture as $key => $value) {
            $total_area_per_culture[$key] = number_format(($value / $total_area) * 100, 2);
            // substituindo id pelo nome da cultura
            $product = Product::find($key);
            $total_area_per_culture[$product->name] = $total_area_per_culture[$key];

            // calculando o ha de acordo com a %
            $total_ha_per_culture[$product->name] = number_format($total_ha_per_culture[$key], 2, ',', '.');

            unset($total_area_per_culture[$key]);
            unset($total_ha_per_culture[$key]);
        }


        foreach ($total_area_per_culture_code as $key => $value) {
            $total_area_per_culture_code[$key] = number_format(($value / $total_area) * 100, 2);
            $total_ha_per_culture_code[$key] = number_format($total_ha_per_culture_code[$key], 2, ',', '.');
        }

        $is_different = $total_area > $total_area_crops;


        return [$data_seeds, 0, $total_area, $total_area_per_culture, $total_area_per_culture_code, $total_ha_per_culture, $total_ha_per_culture_code, [$properties_name, $harvests_name, $crops_name, $culture_name], $is_different];
    }

    public static function readDefensives($admin_id, $request, $page = null, $export = false)
    {
        $join = PropertyCropJoin::find($request->get("property_crop_join_id"));

        return $join;
    }
    public static function readCrops($admin_id, $request, $page = null, $export = false)
    {
        list($crops, $total) = Crop::readCrops($admin_id, null, $page, [], $request->get("property_id"), $request->get("city"), true);

        return $crops;
    }

    public static function readAssets($admin_id, $request, $page = null, $export = false)
    {
        list($assets, $total) = Asset::readAssets($admin_id, $request->get("property_id"), $page, null);
        return $assets;
    }

    public static function readStocksReport($admin_id, $request, $page = null, $export = false, $tab)
    {
        switch ($tab) {
            case 1:
                list($stocks, $total) = Stock::readStocks($admin_id, $request);
                break;
            case 2:
                list($stocks, $total) = StockIncoming::readIncomings($admin_id,  $request);
                break;
            case 3:
                list($stocks, $total) = StockExit::readExits($admin_id,  $request);
                break;
        }
        return $stocks;
    }
}
