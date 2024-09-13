<?php

namespace App\Models;

class ClientManagement extends BaseModel
{
    protected $table = 'client_management';

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

    public static function readClients($admin_id, $filter, $page, $only_active = false)
    {
        $itens = self::orderBy("name", "ASC");

        if ($only_active) {
            $itens = $itens->where('status', 1);
        } else {
            $itens = $itens->where('status', '!=', 0);
        }

        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens = $itens->where("admin_id", $admin_id);
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%")
                    ->orWhere('phone', 'like', "%{$filter}%")
                    ->orWhere('branch_of_activity', 'like', "%{$filter}%");
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

    public static function readClient($id, $admin_id = null)
    {
        $supplier = self::select('*')->where('status', '!=', 0)->find($id);

        return $supplier;
    }
}
