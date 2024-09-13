<?php

namespace App\Models;


class FinancialTransfer extends BaseModel
{
    protected $table = 'financial_transfers';

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

    public function files()
    {
        return $this->hasMany(FinancialTransferFile::class, 'financial_transfer_id', 'id')->where("status", 1);
    }

    public function origin_bank()
    {
        return $this->belongsTo(BankAccountManagement::class, 'origin_bank_account_id', 'id');
    }

    public function destiny_bank()
    {
        return $this->belongsTo(BankAccountManagement::class, 'destiny_bank_account_id', 'id')->with('bank');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'external_account_bank_id', 'id');
    }

    public static function readAll()
    {
        $itens = self::with(['files', 'origin_bank', 'destiny_bank', 'bank'])->where('status', 1)->orderBy("date", "desc")->get();

        return $itens;
    }

    public static function readOne($id)
    {
        $item = self::with(['files', 'origin_bank', 'destiny_bank', 'bank'])->find($id);

        return $item;
    }
}
