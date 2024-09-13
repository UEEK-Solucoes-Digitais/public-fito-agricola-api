<?php

namespace App\Models;

class ContentVideo extends BaseModel
{
    protected $table = 'content_videos';

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    public function watched_contents()
    {
        return $this->hasMany(AdminWatchedContent::class, 'content_video_id', 'id');
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
