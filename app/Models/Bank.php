<?php

namespace App\Models;


class Bank extends BaseModel
{
    protected $table = 'banks';

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

    public static function readBanks($filter, $page)
    {
        $itens = self::where('status', '!=', 0)->orderBy("name", "ASC");


        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%");
            });
        }


        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 100;
            $itens = $itens->skip($skip)->take(100)->get();
        } else {
            $itens = $itens->get();
        }

        return [$itens, $total];
    }

    public static function readBank($id, $admin_id = null)
    {
        $supplier = self::select('*')->where('status', '!=', 0)->find($id);

        return $supplier;
    }
}
