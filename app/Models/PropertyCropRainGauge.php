<?php

namespace App\Models;

use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class PropertyCropRainGauge extends BaseModel
{
    protected $table = 'properties_crops_rain_gauge';
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

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id")->where('status', 1);
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
