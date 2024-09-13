<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PropertyManagementDataPopulation extends BaseModel
{
    protected $table = 'properties_management_data_population';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id");
    }

    public function data_seed()
    {
        return $this->belongsTo(PropertyManagementDataSeed::class, "property_management_data_seed_id", "id")->with('product');
    }

    public function system_log()
    {
        return $this->hasOne(LogSystem::class, 'object_id', 'id')->where('table_name', self::getTableName())->where('operation', 1)->with('admin');
    }

    public static function readDataPopulations()
    {

        $itens = PropertyManagementDataPopulation::select("*")->where('status', 1)->orderBy("id", "DESC");

        return $itens->get();
    }

    public static function readDataPopulation($id)
    {
        return PropertyManagementDataPopulation::select("*")->where('status', 1)->find($id);
    }

    public static function readDataPopulationByCropJoinId($property_crop_join_id)
    {
        return PropertyManagementDataPopulation::select("*")->with('system_log')->with('data_seed')
            ->where('status', 1)
            ->where('properties_crops_id', $property_crop_join_id)
            ->orderBy('id', 'desc')
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

                // Adiciona a conversão diretamente na consulta
                $builder->addSelect('*', DB::raw('plants_per_hectare * 2.42 as plants_per_hectare, quantity_per_ha * 2.42 as quantity_per_ha'));
            }
        });
    }
}
