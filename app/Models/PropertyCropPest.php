<?php

namespace App\Models;

use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class PropertyCropPest extends BaseModel
{
    protected $table = 'properties_crops_pests';
    protected $casts = [
        'coordinates' => Point::class,
    ];

    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, "admin_id", "id");
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id");
    }

    public function pest()
    {
        return $this->belongsTo(InterferenceFactorItem::class, "interference_factors_item_id", "id");
    }

    public function images()
    {
        return $this->hasMany(PropertyCropGallery::class, "object_id", "id")->where("type", 3)->where('status', 1);
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
