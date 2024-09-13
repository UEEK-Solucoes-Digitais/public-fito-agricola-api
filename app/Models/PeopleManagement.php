<?php

namespace App\Models;

class PeopleManagement extends BaseModel
{
    protected $table = 'people_management';

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

    public static function readPeople($admin_id, $filter, $page, $read_internal = false)
    {
        $itens = self::orderBy("name", "ASC");

        if ($read_internal) {
            $itens = $itens->where('status', 1)->where('type', 1);
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
                    ->orWhere('phone', 'like', "%{$filter}%");
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

    public static function readPerson($id, $admin_id = null)
    {
        $person = self::select('*')->where('status', '!=', 0)->find($id);

        return $person;
    }
}
