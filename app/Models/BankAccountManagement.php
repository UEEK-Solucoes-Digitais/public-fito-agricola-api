<?php

namespace App\Models;


class BankAccountManagement extends BaseModel
{
    protected $table = 'bank_account_management';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id')->where('status', '!=', 0);
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

    public function banks()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id')->where('status', '!=', 0);
    }

    public static function readAccounts($admin_id, $filter, $page)
    {
        $itens = self::where('status', '!=', '0')->with('bank');


        // se o usuário que está lendo não for admin, somente os produtos cadastrados por ele serão lidos
        $admin = Admin::find($admin_id);
        if ($admin->access_level != 1) {
            $itens = $itens->where("admin_id", $admin_id);
        }

        if ($filter && $filter != 'null') {
            $itens->where(function ($q) use ($filter) {
                $q->whereHas('bank', function ($sq) use ($filter) {
                    $sq->where('name', 'like', "%{$filter}%");
                })
                ->orWhere('account', 'like', "%{$filter}%")
                ->orWhere('agency', 'like', "%{$filter}%");
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

    public static function readAccount($id, $admin_id = null)
    {
        $account = self::select('*')->where('status', '!=', 0)->find($id);

        return $account;
    }
}
