<?php

namespace App\Models;


class AdminProperty extends BaseModel
{
    protected $table = 'admins_properties';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
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
