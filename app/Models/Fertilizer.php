<?php

namespace App\Models;


class Fertilizer extends BaseModel
{
    protected $table = 'fertilizers';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readFertilizers($filter = null, $page)
    {
        $itens = Fertilizer::where('status', 1)->orderBy("name", "ASC");

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

    public static function readFertilizer($id)
    {
        return Fertilizer::where('status', 1)->find($id);
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
