<?php

namespace App\Models;

class LogSystem extends BaseModel
{
    protected $table = 'log_system';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function data_seed()
    {
        return $this->belongsTo(PropertyManagementDataSeed::class, 'object_id', 'id');
    }

    public function data_input()
    {
        return $this->belongsTo(PropertyManagementDataInput::class, 'object_id', 'id');
    }

    public function data_population()
    {
        return $this->belongsTo(PropertyManagementDataPopulation::class, 'object_id', 'id')->select('*');
    }


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
