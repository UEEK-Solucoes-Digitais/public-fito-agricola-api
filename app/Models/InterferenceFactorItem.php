<?php

namespace App\Models;


class InterferenceFactorItem extends BaseModel
{
    const DANINHA = 1;
    const DOENCA = 2;
    const PRAGA = 3;

    protected $table = 'interference_factors_items';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function cultures()
    {
        return $this->belongsToMany(Product::class, 'diseases_cultures_join', 'disease_id', 'product_id');
    }

    public function cultures_pests()
    {
        return $this->belongsToMany(Product::class, 'pests_cultures_join', 'pest_id', 'product_id');
    }

    public static function readInterferenceFactorItems($type = null, $filter = null, $page)
    {
        $itens = InterferenceFactorItem::where('status', 1)->orderBy("name", "ASC");

        if ($type && $type != 0) {
            $itens->where('type', $type);

            if ($type == 2) {
                $itens->with('cultures');
            }
            if ($type == 3) {
                $itens->with('cultures_pests');
            }
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('observation', 'like', "%{$filter}%")
                    ->orWhere('scientific_name', 'like', "%{$filter}%");
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

    public static function readInterferenceFactorItem($id)
    {
        return InterferenceFactorItem::where('status', 1)->find($id);
    }

    public static function getRole($constant)
    {
        switch ($constant) {
            case self::DANINHA:
                return 'Daninha';
                break;
            case self::DOENCA:
                return 'Doença';
                break;
            case self::PRAGA:
                return 'Praga';
                break;
            default:
                return 'Não definido';
                break;
        }
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
