<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class Property extends BaseModel
{
    protected $table = 'properties';

    protected $casts = [
        'coordinates' => Point::class,
    ];

    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, "admin_id", "id");
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, "admins_properties", "property_id", "admin_id")->orderBy("name", 'asc');
    }

    public function crops()
    {
        return $this->hasMany(PropertyCropJoin::class, "property_id", "id")->where("properties_crops_join.status", 1)->whereHas("crop")->whereHas("harvest")->select('*');
    }

    public function stock_incomings()
    {
        return $this->hasMany(StockIncoming::class, "property_id", "id")->where("status", 1);
    }

    public static function readPropertiesMinimum($admin_id)
    {
        $properties = Property::where('status', '!=', 0)->orderBy("name", "ASC");

        if ($admin_id) {
            $admin = Admin::find($admin_id);

            if ($admin->access_level != 1) {
                $properties->where(function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            }
        }

        $properties = $properties->get();

        return $properties;
    }

    public static function readProperties($admin_id, $filter, $page = null, $select = [], $filter_harvest = false)
    {
        $admin = Admin::find($admin_id);
        $itens = Property::with(["admin" => function ($q) {
            $q->select('id', 'name', 'email', 'phone');
        }])->with(['crops' => function ($q) use ($admin, $filter_harvest) {
            $q->whereHas('harvest', function ($q) use ($admin, $filter_harvest) {

                if ($admin->actual_harvest_id && $filter_harvest) {
                    $q->where('id', $admin->actual_harvest_id);
                } else {
                    $q->where('is_last_harvest', 1);
                }
            });
        }])->where('status', '!=', 0)->orderBy("name", "ASC");

        if ($select) {
            $itens->select($select);
        }

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        if ($admin->access_level != 1) {
            $itens->where(function ($q) use ($admin_id) {
                $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id);
                });
            });
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('cep', 'like', "%{$filter}%")
                    ->orWhere('city', 'like', "%{$filter}%")
                    ->orWhere('street', 'like', "%{$filter}%")
                    ->orWhere('neighborhood', 'like', "%{$filter}%")
                    ->orWhere('number', 'like', "%{$filter}%")
                    ->orWhere('complement', 'like', "%{$filter}%")
                    ->orWhere('state_subscription', 'like', "%{$filter}%")
                    ->orWhere('uf', 'like', "%{$filter}%")
                    ->orWhere(function ($q) use ($filter) {
                        $q->whereHas('admin', function ($q) use ($filter) {
                            $q->where('name', 'like', "%{$filter}%");
                        })->orWhereHas('admins', function ($q) use ($filter) {
                            $q->where('name', 'like', "%{$filter}%");
                        });
                    });
            });
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 10;
            $itens = $itens->skip($skip)->take(10)->get();
        } else {
            $itens = $itens->get();
        }

        $harvest = $admin->actual_harvest_id && $filter_harvest ? Harvest::find($admin->actual_harvest_id) : Harvest::select("id")->where("status", 1)->where('is_last_harvest', 1)->first();
        $itens->map(function ($property) use ($harvest) {

            $total_area = $property->crops->where('harvest_id', $harvest->id)->sum(function ($cropJoin) use (&$data_seed_area) {
                $area = $cropJoin->crop->area ?? 0;
                unset($cropJoin->crop);
                return $area;
            });

            $property->different_area = $total_area < PropertyManagementDataSeed::whereHas('property_crop', function ($q) use ($property, $harvest) {
                $q->where('property_id', $property->id)->where('harvest_id', $harvest->id);
            })->where('status', 1)->sum('area');
            $property->total_area = number_format($total_area, 2, ",", ".");

            return $property;
        });

        return [$itens, $total];
    }

    public static function readProperty($id, $harvest_id = "", $filter = null, $with_draw_area = false)
    {
        $harvest_id_query = $harvest_id ? $harvest_id : (Harvest::select("id")->where("status", 1)->where('is_last_harvest', 1)->first() ? Harvest::select("id")->where("status", 1)->where('is_last_harvest', 1)->first()->id : 0);

        $property = Property::where('status', '!=', 0)
            ->with([
                "admin" => function ($q) {
                    $q->select('id', 'name', 'email', 'phone');
                },
                "crops" => function ($q) use ($harvest_id_query, $filter, $with_draw_area) {
                    $q->where("harvest_id", $harvest_id_query);

                    if ($filter && $filter != 'null') {
                        $q->where(function ($q) use ($filter) {
                            $q->whereHas('crop', function ($q) use ($filter) {
                                $q->where('name', 'like', "%{$filter}%");
                            });
                        });
                    }

                    $q->select('properties_crops_join.*')
                        ->leftJoin("crops", "crops.id", "=", "properties_crops_join.crop_id")
                        ->leftJoin('properties_management_data_seeds', 'properties_management_data_seeds.properties_crops_id', '=', 'properties_crops_join.id')
                        ->leftJoin('products', 'products.id', '=', 'properties_management_data_seeds.product_id')
                        ->leftJoin('properties_management_data_harvest', 'properties_management_data_harvest.properties_crops_id', '=', 'properties_crops_join.id')
                        ->orderBy("products.name", "desc")
                        ->orderBy("crops.name", "ASC")
                        ->orderBy("properties_management_data_seeds.date", "asc")
                        ->orderBy("properties_management_data_harvest.date", "desc")
                        ->where('properties_crops_join.status', 1)
                        ->groupBy('properties_crops_join.id');


                    $q->with([
                        "crop" => function ($q) use ($with_draw_area) {
                            if ($with_draw_area) {
                                $q->select('id', 'name', 'area', 'draw_area');
                            } else {
                                $q->select('id', 'name', 'area');
                            }
                        },
                        "harvest" => function ($q) {
                            $q->select('id', 'name');
                        },
                    ]);
                }
            ])->find($id);

        if ($property) {
            $property->crops->map(function ($crop_item) {
                $crop_item = self::proccessCropItem($crop_item);
                return $crop_item;
            });
        }

        return $property;
    }


    protected static function proccessCropItem($crop_item)
    {
        if ($crop_item->data_seed->first()) {
            // primeiro lemos todos os data_seeds do join
            $data_seeds = PropertyManagementDataSeed::where('properties_crops_id', $crop_item->id)->orderBy('area', 'desc')->where('status', 1)->get();

            // pegamos as culturas dos data_seeds
            $cultures = Product::select("id", "name", "color")->whereIn('id', $data_seeds->pluck('product_id')->toArray())->where('status', 1)->get();
            $crop_item->color =  $data_seeds->first()->product->color;
            $crop_item->culture_table = join('/', $cultures->pluck('name')->toArray());
        } else {
            $crop_item->culture_table = '--';
            $crop_item->color = null;
        }
        // $crop_item->culture_table = optional($crop_item->data_seed->first())->product->name ?? '--';

        $now = $crop_item->data_harvest->first() ? Carbon::createFromFormat('Y-m-d', $crop_item->data_harvest->first()->date) : Carbon::now();

        $emergencyDate = optional($crop_item->data_population->sortByDesc('emergency_percentage_date')->first())->emergency_percentage_date;
        $crop_item->emergency_table = $emergencyDate ? Carbon::createFromFormat('Y-m-d', $emergencyDate)->format("d/m/Y") . ' - DAE ' . Carbon::createFromFormat('Y-m-d', $emergencyDate)->diffInDays($now) . ' dias' : '--';

        $plantDate = optional($crop_item->data_seed->sortByDesc('date')->first())->date;
        // dd([$plantDate, $now]);
        $crop_item->plant_table = $plantDate ? Carbon::createFromFormat('Y-m-d', $plantDate)->format("d/m/Y") . ' - DAP ' . Carbon::createFromFormat('Y-m-d', $plantDate)->diffInDays($now) . ' dias' : '--';


        $applicationDate = optional($crop_item->data_input->where('type', 2)->sortByDesc('date')->first())->date;
        $crop_item->application_table = $applicationDate ? Carbon::createFromFormat('Y-m-d', $applicationDate)->format("d/m/Y") . ' (n.2) - DAA ' . Carbon::createFromFormat('Y-m-d', $applicationDate)->diffInDays($now) . ' dias' : '--';

        $crop_item->productivity = optional($crop_item->data_harvest->first())->productivity ?? '--';
        $crop_item->total_production = optional($crop_item->data_harvest->first())->total_production ?? '--';

        $crop_item->different_area = $crop_item->crop->area < $crop_item->data_seed->sum('area');

        unset($crop_item->data_seed);
        unset($crop_item->data_population);
        unset($crop_item->data_input);
        unset($crop_item->data_harvest);


        return $crop_item;
    }

    public static function readManagementData($property_crop_join_id)
    {
        $stages = PropertyCropStage::where('status', 1)
            ->where('properties_crops_id', $property_crop_join_id)
            ->with(['admin', 'images'])
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return (new \DateTime($item->open_date))->format('d-m-Y');
            });

        $diseases = PropertyCropDisease::where('status', 1)
            ->with(['disease', 'images'])
            ->where('properties_crops_id', $property_crop_join_id)
            ->with('admin')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return (new \DateTime($item->open_date))->format('d-m-Y');
            });

        $pests = PropertyCropPest::where('status', 1)
            ->with(['pest', 'images'])
            ->where('properties_crops_id', $property_crop_join_id)
            ->with('admin')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return (new \DateTime($item->open_date))->format('d-m-Y');
            });

        $weeds = PropertyCropWeed::where('status', 1)
            ->with(['weed', 'images'])
            ->where('properties_crops_id', $property_crop_join_id)
            ->with('admin')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return (new \DateTime($item->open_date))->format('d-m-Y');
            });

        $observations = PropertyCropObservation::where('status', 1)
            ->where('properties_crops_id', $property_crop_join_id)
            ->with(['admin', 'images'])
            ->orderBy('id', 'desc')
            ->get()
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
            $admin = null;

            if ($stages->get($date)) {
                $admin = $stages->get($date)[0]->admin;
            } else if ($diseases->get($date)) {
                $admin = $diseases->get($date)[0]->admin;
            } else if ($pests->get($date)) {
                $admin = $pests->get($date)[0]->admin;
            } else if ($weeds->get($date)) {
                $admin = $weeds->get($date)[0]->admin;
            } else if ($observations->get($date)) {
                $admin = $observations->get($date)[0]->admin;
            }

            $management_data[$date] = [
                'stages' => $stages->get($date) ?: [],
                'diseases' => $diseases->get($date) ?: [],
                'pests' => $pests->get($date) ?: [],
                'weeds' => $weeds->get($date) ?: [],
                'observations' => $observations->get($date) ?: [],
                'admin' => $admin,
            ];
        }

        return $management_data;
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            createLogSystem($item->id, self::getTableName(), 1);
        });

        static::updated(function ($item) {
            createLogSystem($item->id, self::getTableName(), 2, $item->getOriginal(), $item->getDirty());
        });
    }
}
