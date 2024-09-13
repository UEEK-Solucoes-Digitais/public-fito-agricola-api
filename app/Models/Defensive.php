<?php

namespace App\Models;


class Defensive extends BaseModel
{
    protected $table = 'defensives';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readDefensives($filter, $page)
    {
        $itens = Defensive::where('status', 1)->orderBy("name", "ASC");

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('observation', 'like', "%{$filter}%");
            });
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 10;
            $itens = $itens->skip($skip)->take(10)->get();
        } else {
            $itens = $itens->get();
        }

        return [$itens, $total];
    }

    public static function readDefensive($id)
    {
        return Defensive::where('status', 1)->find($id);
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
