<?php

namespace App\Models;

class ContentCategory extends BaseModel
{
    protected $table = 'contents_categories';

    public static function readCategories()
    {
        $itens = self::where('status', 1)->orderBy("name", "ASC")->get();

        return $itens;
    }

    public static function readCategoriesByPosition()
    {
        $itens = self::where('status', 1)->orderBy("position", "ASC")->get();

        return $itens;
    }

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
}
