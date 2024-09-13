<?php

namespace App\Models;

use DateTimeInterface;

class StockExit extends BaseModel
{
    protected $table = 'stock_exits';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, "stock_id", "id")->with('product')->where("status", 1);
    }

    public function crop_join()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id")->with('crop')->with('property')->with('harvest')->whereHas("property")->whereHas("crop")->whereHas("harvest")->where("status", 1);
    }

    public function data_seed()
    {
        return $this->belongsTo(PropertyManagementDataSeed::class, "object_id", "id")->where("status", 1);
    }

    public function data_input()
    {
        return $this->belongsTo(PropertyManagementDataInput::class, "object_id", "id")->where("status", 1);
    }

    public static function readExits($admin_id, $request)
    {
        $itens = self::with("stock.product")
            ->with('crop_join')
            ->whereHas('crop_join')
            ->whereHas('stock')
            ->join('stocks', 'stocks.id', '=', 'stock_exits.stock_id')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('properties', 'properties.id', '=', 'stocks.property_id')
            ->select('stock_exits.*')
            ->orderBy('properties.name', 'asc')
            ->orderBy('products.type', 'desc')
            ->orderBy('products.name', 'asc')
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('stock_exits.type', 'seed')->whereHas('data_seed');
                })->orWhere(function ($q) {
                    $q->whereIn('stock_exits.type', ['defensive', 'fertilizer'])->whereHas('data_input');
                });
            })
            ->orderBy('stock_exits.created_at', 'asc');

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens->whereHas('stock.product', function ($q) use ($admin_id) {
                $q->whereIn('admin_id', [0, $admin_id]);
            })->whereHas('stock.property', function ($q) use ($admin_id) {
                $q->where(function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            });
        }

        if ($request->get("filter")) {
            $itens->where(function ($q) use ($request) {
                $q->whereHas('stock.product', function ($q) use ($request) {
                    $q->where('name', 'like', "%" . $request->get("filter") . "%");
                });
            });
        }

        if ($request->get("properties_id")) {
            $itens->whereHas('stock', function ($q) use ($request) {
                $q->whereIn("property_id", explode(",", $request->get("properties_id")));
            });
        }

        if ($request->get("crops_id")) {
            $itens->whereHas("crop_join", function ($q) use ($request) {
                $q->whereIn("crop_id", explode(",", $request->get("crops_id")));
            });
        }

        if ($request->get("product_type")) {
            if (in_array($request->get("product_type"), ["1", "3"])) {
                $itens->whereHas("stock.product", function ($q) use ($request) {
                    $q->where("type", $request->get("product_type"));
                });
            } else {
                $itens->whereHas("stock.product", function ($q) use ($request) {
                    $q->where("object_type", floatval($request->get("product_type")) - 3);
                });
            }
        }

        if ($request->get("products_id")) {
            $itens->whereHas('stock', function ($q) use ($request) {
                $q->whereIn('product_id', explode(",", $request->get("products_id")));
            });
        }

        $total = $itens->count();

        if ($request->get("page")) {
            $skip = ($request->get("page") - 1) * 100;
            $itens = $itens->skip($skip)->take(100)->get();
        } else {
            $itens = $itens->get();
        }

        return [$itens, $total];
    }

    public static function readIncoming($id)
    {
        return self::with("stock")->where('status', '!=', 0)->find($id);
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
