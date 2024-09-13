<?php

namespace App\Models;


class FinancialCategory extends BaseModel
{
    protected $table = 'financial_categories';

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

    public static function readAll()
    {
        $itens = self::orderBy("name", "ASC")->get()->groupBy('type');

        return $itens;
    }

    public static function readOne($id)
    {
        $item = self::find($id);

        return $item;
    }
}
