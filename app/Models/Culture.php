<?php

namespace App\Models;


class Culture extends BaseModel
{
    protected $table = 'cultures';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readCultures($filter, $page)
    {
        $itens = Culture::whereIn('status', [1, 2])->orderBy("name", "ASC");

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('code', 'like', "%{$filter}%");
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

    public static function readCulture($id)
    {
        return Culture::whereIn('status', [1, 2])->find($id);
    }

    public function interference_factors_items()
    {
        return $this->belongsToMany(Culture::class, "interference_factors_items_cultures_join", "culture_id", "interference_factors_item_id",  'id', 'id');
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
