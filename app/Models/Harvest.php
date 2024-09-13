<?php

namespace App\Models;


class Harvest extends BaseModel
{
    protected $table = 'harvests';

    public function crops_join()
    {
        return $this->hasMany(PropertyCropJoin::class, 'harvest_id', 'id')->with(['property', 'crop'])->whereHas("crop")->whereHas("property");
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readHarvests($filter, $page, $select = [])
    {
        $itens = Harvest::where('status', 1)->orderBy("name", "asc");

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('start_date', 'like', "%{$filter}%")
                    ->orWhere('end_date', 'like', "%{$filter}%");
            });
        }

        if ($select) {
            $itens->select($select);
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

    public static function readHarvest($id)
    {
        return Harvest::with("crops_join")->where('status', 1)->find($id);
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
