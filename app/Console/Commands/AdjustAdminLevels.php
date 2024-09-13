<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class AdjustAdminLevels extends Command
{
    protected $signature = 'app:adjust-admin-levels';


    protected $description = 'Ajustar níveis dos admins';

    public function handle()
    {
        checkSection(1);

        $admins_ma = Admin::where("status", 1)->where("access_level", 4)->get();
        foreach ($admins_ma as $admin) {
            if ($admin->level != Admin::MA_ACCESS) {
                echo "Ajustando permissões do usuário - {$admin->name} \n";
                $admin->level = Admin::MA_ACCESS;
                $admin->save();
            }
        }

        $admins_producers = Admin::where("status", 1)->whereIn("access_level", [2, 3])->get();
        foreach ($admins_producers as $admin) {
            if ($admin->level != Admin::CONSULTANT_AND_PRODUCER_ACCESS) {
                echo "Ajustando permissões do usuário - {$admin->name} \n";
                $admin->level = Admin::CONSULTANT_AND_PRODUCER_ACCESS;
                $admin->save();
            }
        }

        // $admins_team = Admin::where("status", 1)->where("access_level", 5)->get();

        // foreach ($admins_team as $admin) {
        //     $admin->level = Admin::TEAM_ACCESS;
        //     $admin->save();
        // }
    }
}
