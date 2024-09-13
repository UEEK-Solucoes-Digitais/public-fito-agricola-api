<?php

namespace App\Models;


class FinancialPaymentMethod extends BaseModel
{
    protected $table = 'financial_payment_methods';

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
        $itens = self::orderBy("name", "ASC")->get();

        return $itens;
    }

    public static function readOne($id)
    {
        $item = self::find($id);

        return $item;
    }
}
