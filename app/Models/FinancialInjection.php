<?php

namespace App\Models;


class FinancialInjection extends BaseModel
{
    protected $table = 'financial_injection';

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

    public static function readAll($admin_id, $page)
    {
        $itens = self::where('status', 1)->orderBy("date", "DESC");

        $admin = Admin::find($admin_id);

        if ($admin->access_level != 1) {
            $itens = $itens->where('admin_id', $admin_id);
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 25;
            $itens = $itens->skip($skip)->take(25)->get();
        } else {
            $itens = $itens->get();
        }

        return [$itens, $total];
    }

    public static function readOne($id)
    {
        $item = self::where('status', 1)->find($id);

        return $item;
    }
}
