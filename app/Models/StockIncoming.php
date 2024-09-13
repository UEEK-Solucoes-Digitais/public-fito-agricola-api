<?php

namespace App\Models;

use DateTimeInterface;

class StockIncoming extends BaseModel
{
    protected $table = 'stock_incomings';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    protected $casts = [
        'entry_date' => 'datetime:d/m/Y',
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }


    public function stock()
    {
        return $this->belongsTo(Stock::class, "stock_id", "id")->where("status", 1)->with('product')->with("property")->whereHas("product");
    }

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id", "id")->where("status", 1);
    }

    // public function stock_incomings()
    // {
    //     return $this->hasMany(StockExit::class, "stock_incoming_id", "id");
    // }

    // public function property()
    // {
    //     return $this->belongsTo(Stock::class, "stock_id", "id");
    // }

    public static function readIncomings($admin_id, $request, $type = null)
    {
        $itens = self::whereHas('stock')
            ->with("stock")
            ->where('stock_incomings.status', '!=', 0)
            ->join('stocks', 'stocks.id', '=', 'stock_incomings.stock_id')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('properties', 'properties.id', '=', 'stocks.property_id')
            ->select('stock_incomings.*')
            ->orderBy('properties.name', 'asc')
            ->orderBy('products.type', 'desc')
            ->orderBy('products.name', 'asc')
            ->orderBy('stock_incomings.created_at', 'asc');

        if ($type) {
            $itens = $itens->whereHas('stock.product', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens = $itens->whereHas('stock.product', function ($q) use ($admin_id) {
                $q->whereIn('admin_id', [0, $admin_id]);
            })->whereHas('stock.property', function ($q) use ($admin_id) {
                $q->where(function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            });
        }

        if ($request->get('filter')) {
            $itens = $itens->where(function ($q) use ($request) {
                $q->whereHas('stock.product', function ($q) use ($request) {
                    $q->where('name', 'like', "%" . $request->get("filter") . "%");
                });
            });
        }

        if ($request->get("properties_id")) {
            $itens->whereHas("stock", function ($q) use ($request) {
                $q->whereIn("property_id", explode(",", $request->get("properties_id")));
            });
        }

        if ($request->get("crops_id")) {
            $itens->whereHas("stock.stock_exits", function ($q) use ($request) {
                $q->whereHas("crop_join", function ($q) use ($request) {
                    $q->whereIn("crop_id", explode(",", $request->get("crops_id")));
                });
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
            $itens->whereHas("stock", function ($q) use ($request) {
                $q->whereIn('product_id', explode(",", $request->get("products_id")));
            });
        }

        $total = $itens->count();

        if ($request->get('page')) {
            $skip = ($request->get('page') - 1) * 100;
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
