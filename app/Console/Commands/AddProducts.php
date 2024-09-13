<?php

namespace App\Console\Commands;

use App\Imports\ImportPest;
use App\Imports\ImportProducts;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AddProducts extends Command
{
    protected $signature = 'app:add-products';

    protected $description = 'Importar produtos';

    public function handle()
    {
        // Excel::import(new ImportProducts, public_path('/tables_to_import/diseases.xlsx'));
        Excel::import(new ImportPest, public_path('/tables_to_import/pests.xlsx'));
    }
}
