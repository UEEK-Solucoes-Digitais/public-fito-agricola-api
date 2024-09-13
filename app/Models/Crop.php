<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class Crop extends BaseModel
{
    protected $table = 'crops';

    public function crops_join()
    {
        return $this->hasMany(PropertyCropJoin::class, 'crop_id', 'id')->with(['property', 'harvest'])->whereHas("property")->whereHas("harvest")->where('status', 1);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(CropFile::class, 'crop_id', 'id')->where('status', 1);
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readCrops($admin_id, $filter, $page, $select = [], $property_id = null, $city = null, $filter_harvest = false)
    {
        $itens = Crop::where('status', 1)->orderBy("name", "ASC");

        if ($select) {
            $itens->select($select);
        } else {
            $itens->select("*");
        }

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        if ($admin_id) {
            $admin = Admin::find($admin_id);
            if ($admin->access_level != 1) {
                $itens->whereHas('property', function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            }
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('area', 'like', "%{$filter}%");
            });
        }

        if ($property_id) {
            $itens->where('property_id', $property_id);
        }

        if ($city) {
            $itens->where('city', 'like', "%{$city}%");
        }

        if (isset($admin) && $admin->actual_harvest_id && $filter_harvest) {
            $itens->whereIn('id', PropertyCropJoin::select('crop_id')->where('harvest_id', $admin->actual_harvest_id)->where("status", 1));
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 100;
            $itens = $itens->skip($skip)->take(100)->get();
        } else {
            $itens = $itens->get();
        }

        return [$itens, $total];
    }

    public static function readCrop($id, $admin_id = null)
    {
        $crop = Crop::select('*')->with("crops_join")->with('files')->where('status', 1);

        if ($admin_id) {
            $admin = Admin::find($admin_id);

            if ($admin->access_level != 1) {
                $crop->whereHas('property', function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            }
        }

        return $crop->find($id);
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

    protected static function booted()
    {
        static::addGlobalScope('convertArea', function (Builder $builder) {
            if (request()->query('convert_to_alq', false)) {

                // dd($builder);
                // Adiciona a conversão diretamente na consulta
                $builder->addSelect(DB::raw('area / 2.42 as area'));
            }
        });
    }
}
