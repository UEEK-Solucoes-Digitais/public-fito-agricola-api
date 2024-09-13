<?php

namespace App\Console\Commands;

use App\Imports\ImportMAUsers;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportMAUsersCommand extends Command
{
    protected $signature = 'app:import-m-a-users-command';

    protected $description = 'Importando usuário de M.A';

    public function handle()
    {
        // Excel::import(new ImportProducts, public_path('/tables_to_import/diseases.xlsx'));
        Excel::import(new ImportMAUsers, public_path('/tables_to_import/ma_users.xlsx'));
    }
}
