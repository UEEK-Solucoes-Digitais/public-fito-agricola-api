<?php

namespace App\Models;

class AppVersion extends BaseModel
{
    protected $table = 'app_versions';

    public static function getTableName()
    {
        return (new self())->getTable();
    }
}
