<?php

namespace App\Models;

class ContentBlock extends BaseModel
{
    protected $table = 'contents_blocks';

    public function images()
    {
        return $this->hasMany(ContentBlockGallery::class, "content_block_id", "id")->orderBy("id", "ASC")->where('status', 1);
    }

    public static function getTableName()
    {
        return (new self())->getTable();
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
