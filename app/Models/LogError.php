<?php

namespace App\Models;

class LogError extends BaseModel
{
    protected $table = 'log_errors';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
