<?php

namespace App\Models;


class AdminContentInteractions extends BaseModel
{
    protected $table = 'admin_content_interactions';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
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
