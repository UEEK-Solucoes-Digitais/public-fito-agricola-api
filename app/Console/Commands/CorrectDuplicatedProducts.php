<?php

namespace App\Console\Commands;

use App\Imports\CorrectDuplicatedProductsImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class CorrectDuplicatedProducts extends Command
{
    protected $signature = 'app:correct-duplicated-products';

    protected $description = 'Produtos no sistema estão duplicados e serão corrigidos conforme planilha';

    public function handle()
    {
        Excel::import(new CorrectDuplicatedProductsImport, public_path('/tables_to_import/corrected_products.xlsx'));
    }
}
