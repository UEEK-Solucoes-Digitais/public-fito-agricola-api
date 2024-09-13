<?php

namespace App\Models;

use DateTimeInterface;

class Stock extends BaseModel
{
    protected $table = 'stocks';

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id")->where("status", 1);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id", "id")->where("status", 1);
    }

    public function stock_incomings()
    {
        return $this->hasMany(StockIncoming::class, "stock_id", "id")->where('status', 1);
    }

    public function stock_exits()
    {
        return $this->hasMany(StockExit::class, "stock_id", "id")->whereHas('crop_join')->where(function ($q) {
            $q->where(function ($q) {
                $q->where('type', 'seed')->whereHas('data_seed');
            })->orWhere(function ($q) {
                $q->whereIn('type', ['defensive', 'fertilizer'])->whereHas('data_input');
            });
        });
    }

    public static function readStocks($admin_id, $request)
    {
        $itens = self::with(['product', 'property', 'stock_incomings'])
            ->whereHas("product")
            ->whereHas("stock_exits")
            ->where('stocks.status', '!=', 0)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('properties', 'properties.id', '=', 'stocks.property_id')
            ->select('stocks.*')
            ->orderBy('properties.name', 'asc')
            ->orderBy('products.type', 'desc')
            ->orderBy('products.name', 'asc')
            ->orderBy('stocks.created_at', 'asc');

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens->whereHas('product', function ($q) use ($admin_id) {
                $q->whereIn('admin_id', [0, $admin_id]);
            })->whereHas('property', function ($q) use ($admin_id) {
                $q->where(function ($q) use ($admin_id) {
                    $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id);
                    });
                });
            });
        }

        if ($request->get("filter")) {
            $itens->where(function ($q) use ($request) {
                $q->whereHas('product', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->get('filter') . '%');
                });
            });
        }

        if ($request->get("properties_id")) {
            $itens->whereIn("stocks.property_id", explode(",", $request->get("properties_id")));
        }

        if ($request->get("crops_id")) {
            $itens->whereHas("stock_exits", function ($q) use ($request) {
                $q->whereHas("crop_join", function ($q) use ($request) {
                    $q->whereIn("crop_id", explode(",", $request->get("crops_id")));
                });
            });
        }

        if ($request->get("product_type")) {
            if (in_array($request->get("product_type"), ["1", "3"])) {
                $itens->whereHas("product", function ($q) use ($request) {
                    $q->where("type", $request->get("product_type"));
                });
            } else {
                $itens->whereHas("product", function ($q) use ($request) {
                    $q->where("object_type", floatval($request->get("product_type")) - 3);
                });
            }
        }

        if ($request->get("product_variant")) {
            $itens->where('stocks.product_variant', $request->get("product_variant"));
        }

        if ($request->get("products_id")) {
            $itens->whereIn('stocks.product_id', explode(",", $request->get("products_id")));
        }




        $total = $itens->count();

        if ($request->get('page')) {
            $skip = ($request->get('page') - 1) * 100;
            $itens = $itens->skip($skip)->take(100)->get();
        } else {
            $itens = $itens->get();
        }

        // $itens = $itens->sortBy(function ($item) {
        //     return $item->product->name;
        // })->values();

        // contando estoque de stock_incoming - stock_exit
        $itens = $itens->each(function ($item) {
            $quantity = 0;

            foreach ($item->stock_incomings as $incoming) {
                $quantity += $incoming->quantity;
            }

            $quantity = $quantity - $item->stock_exits->sum('quantity');

            $item->stock_quantity_number = $quantity;
            $item->stock_quantity = number_format($quantity, 2, ',', '.');
        });

        if ($request->get("not_show_zero")) {
            $itens = $itens->where('stock_quantity_number', '!=', 0)->values();
        }

        // dd($itens);

        return [$itens, $total];
    }

    public static function readStock($id)
    {
        return self::with(['product', 'property', 'stock_incomings'])->where('status', '!=', 0)->find($id);
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
