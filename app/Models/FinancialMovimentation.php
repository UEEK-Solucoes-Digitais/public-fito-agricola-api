<?php

namespace App\Models;


class FinancialMovimentation extends BaseModel
{
    protected $table = 'financial_movimentations';

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

    public function client()
    {
        return $this->belongsTo(ClientManagement::class, 'client_management_id', 'id')->where("status", 1);
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierManagement::class, 'supplier_management_id', 'id')->where("status", 1);
    }

    public function people()
    {
        return $this->belongsTo(PeopleManagement::class, 'people_management_id', 'id')->where("status", 1);
    }

    public function charges()
    {
        return $this->hasMany(FinancialMovimentationCharge::class, 'financial_movimentation_id', 'id')->where("status", 1);
    }

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id', 'id');
    }

    public function tax_type()
    {
        return $this->belongsTo(FinancialTaxType::class, 'financial_tax_type_id', 'id');
    }

    public function payment_method()
    {
        return $this->belongsTo(FinancialPaymentMethod::class, 'financial_payment_method_id', 'id');
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccountManagement::class, 'bank_account_management_id', 'id');
    }

    public static function readAll($admin_id, $request)
    {
        $date_init = $request->get("date_init") && $request->get("date_init") != 'null'  ? $request->get("date_init") : null;
        $date_end = $request->get("date_end") && $request->get("date_end") != 'null'   ? $request->get("date_end") : null;
        $selected_admin_id = $request->get("filter_admin_id") && $request->get("filter_admin_id") != 'null' && $request->get("filter_admin_id") != 0 ? $request->get("filter_admin_id") : null;

        $filter_type = $request->get("type") && $request->get("type") != 'null' ? $request->get("type") : null;
        $filter_subtype = $request->get("subtype") && $request->get("subtype") != 'null' ? $request->get("subtype") : null;

        $init_date = $date_init ? $date_init : date('Y-m-d', strtotime('-1 month'));
        // end_date é a variavel ou o ultimo dia do mes atual
        $end_date = $date_end ? $date_end : date('Y-m-t');

        $charges_query_original = FinancialMovimentationCharge::orderBy('due_date', 'DESC')
            ->with('movimentation')
            ->whereHas('movimentation')
            ->where('status', 1);

        $movimentation_query_original = self::where('payment_type', '!=', 2)
            ->with(['client', 'people', 'supplier', 'category', 'tax_type', 'payment_method', 'bank_account'])
            ->where('status', 1)
            ->orderBy('due_date', 'DESC');

        $transfers_query_original = FinancialTransfer::with(['files', 'origin_bank', 'destiny_bank', 'bank'])
            ->orderBy('due_date', 'DESC')
            ->where('status', 1);

        $injections_query_original = FinancialInjection::orderBy('due_date', 'DESC')
            ->where('status', 1);

        $admin = Admin::find($admin_id);

        if ($admin->access_level != 1) {
            $charges_query_original = $charges_query_original->whereHas('movimentation', function ($query) use ($admin_id) {
                $query->where('admin_id', $admin_id);
            });

            $movimentation_query_original = $movimentation_query_original->where('admin_id', $admin_id);

            $injections_query_original = $injections_query_original->where('admin_id', $admin_id);

            $transfers_query_original = $transfers_query_original->where('admin_id', $admin_id);
        } else if ($selected_admin_id) {
            $charges_query_original = $charges_query_original->whereHas('movimentation', function ($query) use ($selected_admin_id) {
                $query->where('admin_id', $selected_admin_id);
            });

            $movimentation_query_original = $movimentation_query_original->where('admin_id', $selected_admin_id);

            $injections_query_original = $injections_query_original->where('admin_id', $selected_admin_id);

            $transfers_query_original = $transfers_query_original->where('admin_id', $selected_admin_id);
        }

        if ($filter_subtype == 'receipts') {
            $charges_query_original = $charges_query_original->whereHas('movimentation', function ($query) {
                $query->whereIn('type', [1, 3]);
            });

            $movimentation_query_original = $movimentation_query_original->whereIn('type', [1, 3]);
        } else if ($filter_subtype == 'cost') {
            $charges_query_original = $charges_query_original->whereHas('movimentation', function ($query) {
                $query->whereIn('type', [2]);
            });

            $movimentation_query_original = $movimentation_query_original->whereIn('type', [2]);
        }

        $charges = (!$filter_type || $filter_type == 'movimentation') ? collect((clone $charges_query_original)
            ->whereBetween('due_date', [$init_date, $end_date])
            ->get()) : collect([]);

        $movimentations = (!$filter_type || $filter_type == 'movimentation') ? collect((clone $movimentation_query_original)
            ->whereBetween('due_date', [$init_date, $end_date])
            ->get()) : collect([]);

        $injections = (!$filter_type || $filter_type == 'injection')  && (!$filter_subtype || $filter_subtype == 'receipts') ? collect((clone $injections_query_original)
            ->whereBetween('due_date', [$init_date, $end_date])
            ->get()) : collect([]);

        $transfers = (!$filter_type || $filter_type == 'transfer') && (!$filter_subtype || $filter_subtype == 'cost') ? collect((clone $transfers_query_original)
            ->whereBetween('due_date', [$init_date, $end_date])
            ->get()) : collect([]);

        // $itens = $movimentations->concat($charges)->sortByDesc('due_date')->values();
        $itens = $charges->concat($movimentations)->concat($injections)->concat($transfers)->sortByDesc('due_date')->values();

        // dd($itens);

        if (count($itens) == 0 && (!$date_init && !$date_end)) {
            $charges = (!$filter_type || $filter_type == 'movimentation') ?  (clone $charges_query_original)->get() : collect([]);
            $movimentations = (!$filter_type || $filter_type == 'movimentation') ?  (clone $movimentation_query_original)->get() : collect([]);
            $injections = (!$filter_type || $filter_type == 'injection')  && (!$filter_subtype || $filter_subtype == 'receipts') ?  (clone $injections_query_original)->get() : collect([]);
            $transfers = (!$filter_type || $filter_type == 'transfer') && (!$filter_subtype || $filter_subtype == 'cost') ? (clone $transfers_query_original)->get() : collect([]);

            $itens = $charges->concat($movimentations)->concat($injections)->concat($transfers)->sortByDesc('due_date')->values();

            if ($itens->count() > 0) {
                $init_date = $itens->min('due_date');
                $end_date = $itens->max('due_date');
            }
        }

        return [$itens, $init_date, $end_date];
    }

    public static function readOne($id)
    {
        $item = self::with(['client', 'people', 'supplier', 'category', 'tax_type', 'payment_method', 'bank_account', 'charges'])->where('status', 1)->find($id);

        return $item;
    }
}
