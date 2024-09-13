<?php

namespace App\Models;

use DateTimeInterface;

class Notification extends BaseModel
{
    protected $table = 'notifications';

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function admin_responsable()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, 'object_id')->where('status', 1)->whereHas("crop")->whereHas("property");
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'object_id');
    }
}
