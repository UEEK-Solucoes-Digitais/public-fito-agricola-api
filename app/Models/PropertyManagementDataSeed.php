<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PropertyManagementDataSeed extends BaseModel
{
    protected $table = 'properties_management_data_seeds';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function system_log()
    {
        return $this->hasOne(LogSystem::class, 'object_id', 'id')->where('table_name', self::getTableName())->where('operation', 1)->with('admin');
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id");
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id")->with(['diseases' => function ($q) {
            $q->where('diseases_cultures_join.status', 1)->where('interference_factors_items.status', 1)->orderBy('name', 'ASC');
        }, 'pests' => function ($q) {
            $q->where('pests_cultures_join.status', 1)->where('interference_factors_items.status', 1)->orderBy('name', 'ASC');
        }]);
    }

    public function data_population()
    {
        return $this->hasMany(PropertyManagementDataPopulation::class, "property_management_data_seed_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public static function readDataSeeds()
    {

        $itens = PropertyManagementDataSeed::where('status', 1)->orderBy("id", "DESC");

        return $itens->get();
    }

    public static function readDataSeed($id)
    {
        return PropertyManagementDataSeed::where('status', 1)->find($id);
    }

    public static function readDataSeedByJoin($crop_join_id)
    {
        return PropertyManagementDataSeed::with(['product', 'system_log'])
            ->where('status', 1)
            ->where('properties_crops_id', $crop_join_id)
            ->orderBy('date', 'desc')
            ->get();
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

                if ($builder->getQuery()->columns && count($builder->getQuery()->columns) > 0) {
                    $builder->addSelect(DB::raw('area / 2.42 as area'));
                } else {
                    $builder->addSelect('*', DB::raw('area / 2.42 as area'));
                }
            }
        });
    }
}
