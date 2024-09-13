<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = 'products';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function system_log()
    {
        return $this->hasOne(LogSystem::class, 'object_id', 'id')->where('table_name', self::getTableName())->where('operation', 1)->with('admin');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, "admin_id", "id");
    }

    public function seed()
    {
        return $this->hasOne(Culture::class, "id", "item_id")->where('status', '!=', 0);
    }

    public function defensive()
    {
        return $this->hasOne(Defensive::class, "id", "item_id")->where('status', '!=', 0);
    }

    public function fertilizer()
    {
        return $this->hasOne(Fertilizer::class, "id", "item_id")->where('status', '!=', 0);
    }

    public function diseases()
    {
        return $this->belongsToMany(InterferenceFactorItem::class, 'diseases_cultures_join', 'product_id', 'disease_id');
    }

    public function pests()
    {
        return $this->belongsToMany(InterferenceFactorItem::class, 'pests_cultures_join', 'product_id', 'pest_id');
    }

    public static function readProducts($admin_id, $filter, $page, $type = null, $select = [])
    {

        $itens = Product::with("admin")->where('status', '!=', 0)->orderBy("name", "ASC");

        if ($type) {
            $itens->where('type', $type);
        }

        if ($select && count($select) > 0) {
            $itens->select($select);
        }

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens->whereIn('admin_id', [$admin_id, 0]);
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                if (stripos($filter, "tipo") !== false) {
                    $filter = str_replace("tipo:", "", $filter);
                    $q->where('object_type', $filter);
                } else {
                    $q->where('name', 'like', "%{$filter}%")
                        ->orWhere('extra_column', 'like', "%{$filter}%");
                }
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

    public static function readProduct($id)
    {
        return Product::with("admin")->where('status', '!=', 0)->find($id);
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
