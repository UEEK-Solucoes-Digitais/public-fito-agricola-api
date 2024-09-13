<?php

namespace App\Models;

class PropertyCropJoin extends BaseModel
{
    protected $table = 'properties_crops_join';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id", "id")->where('status', 1);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class, "crop_id", "id")->where('status', 1)->select('*');
    }

    public function harvest()
    {
        return $this->belongsTo(Harvest::class, "harvest_id", "id")->where('status', 1);
    }

    public function stage()
    {
        return $this->hasMany(PropertyCropStage::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function diseases()
    {
        return $this->hasMany(PropertyCropDisease::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function pests()
    {
        return $this->hasMany(PropertyCropPest::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function weeds()
    {
        return $this->hasMany(PropertyCropWeed::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function observations()
    {
        return $this->hasMany(PropertyCropObservation::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function rain_gauge()
    {
        return $this->hasMany(PropertyCropRainGauge::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function data_harvest()
    {
        return $this->hasMany(PropertyManagementDataHarvest::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function data_input()
    {
        return $this->hasMany(PropertyManagementDataInput::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC')->with('product');
    }

    public function data_population()
    {
        return $this->hasMany(PropertyManagementDataPopulation::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC');
    }

    public function data_seed()
    {
        return $this->hasMany(PropertyManagementDataSeed::class, "properties_crops_id", "id")->where("status", 1)->orderBy('created_at', 'DESC')->with('product');
    }

    public function stock_exits()
    {
        return $this->hasMany(StockExit::class, "properties_crops_id", "id")->with('stock')->whereHas('stock');
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
