<?php

namespace App\Models;

class PropertyManagementDataHarvest extends BaseModel
{
    protected $table = 'properties_management_data_harvest';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id")->with('crop');
    }

    public function data_seed()
    {
        return $this->belongsTo(PropertyManagementDataSeed::class, "property_management_data_seed_id", "id")->with('product');
    }

    public function system_log()
    {
        return $this->hasOne(LogSystem::class, 'object_id', 'id')->where('table_name', self::getTableName())->where('operation', 1)->with('admin');
    }

    public static function readDataHarvests()
    {

        $itens = PropertyManagementDataHarvest::where('status', 1)->orderBy("id", "DESC");

        return $itens->get();
    }

    public static function readDataHarvest($id)
    {
        return PropertyManagementDataHarvest::where('status', 1)->find($id);
    }

    public static function readDataHarvestByCropJoin($property_crop_join_id)
    {
        return PropertyManagementDataHarvest::with(['system_log', 'data_seed'])
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
}
