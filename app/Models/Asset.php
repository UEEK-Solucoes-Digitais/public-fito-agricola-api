<?php

namespace App\Models;

use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class Asset extends BaseModel
{
    protected $table = 'assets';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function readAssets($admin_id, $property_id = null, $filter, $page)
    {
        $itens = Asset::where('status', 1)->with(['property', 'properties'])->orderBy("name", "ASC");

        //  trazer propriedades ligadas
        if ($property_id) {
            // $itens->whereHas('property', function ($query) use ($property_id) {
            //     $query->where('id', $property_id);
            // });
            $itens->whereHas('properties', function ($query) use ($property_id) {
                $query->where('assets_properties.property_id', $property_id);
            });
        }

        // se o usuário que está lendo não for admin, somente os bens cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens->whereHas("property", function ($q) use ($admin_id) {
                $q->where("admin_id", $admin_id);
            });
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('type', 'like', "%{$filter}%")
                    ->orWhere('value', 'like', "%{$filter}%");
            });
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 10;
            $itens = $itens->skip($skip)->take(10)->get();
        } else {
            $itens = $itens->get();
        }

        $itens->map(function ($item) {
            $item->properties_names = $item->properties->pluck('name')->join(', ');
            return $item;
        });

        return [$itens, $total];
    }

    public static function readAsset($id)
    {
        return Asset::where('status', 1)->with(['property', 'properties'])->find($id);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'assets_properties', 'asset_id', 'property_id');
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
