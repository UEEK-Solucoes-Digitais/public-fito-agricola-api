<?php

namespace App\Models;


class FinancialMovimentationCharge extends BaseModel
{
    protected $table = 'financial_movimentation_charges';

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

    public function payment_method()
    {
        return $this->belongsTo(FinancialPaymentMethod::class, 'financial_payment_method_id', 'id');
    }

    public function movimentation()
    {
        return $this->belongsTo(FinancialMovimentation::class, 'financial_movimentation_id', 'id')->with(['client', 'supplier', 'category', 'tax_type', 'payment_method', 'bank_account', 'people'])->where("status", 1);
    }

    public static function readAll()
    {
        $itens = self::whereHas('movimentation')->with(['movimentation', 'payment_method'])->where('status', 1)->orderBy("date", "DESC")->get();

        return $itens;
    }

    public static function readOne($id)
    {
        $item = self::whereHas('movimentation')->with(['movimentation', 'payment_method'])->where('status', 1)->find($id);

        return $item;
    }
}
