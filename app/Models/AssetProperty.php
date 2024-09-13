<?php

namespace App\Models;


class AssetProperty extends BaseModel
{
    protected $table = 'assets_properties';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
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
