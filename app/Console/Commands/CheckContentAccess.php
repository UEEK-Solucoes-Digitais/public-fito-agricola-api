<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Content;
use App\Models\ContentTypeAccess;
use Illuminate\Console\Command;

class CheckContentAccess extends Command
{
    protected $signature = 'app:check-content-access';

    protected $description = 'Criando permissões de acesso a conteúdos';

    public function handle()
    {
        $admins = Admin::where('access_level', "!=", 1)->where("status", 1)->get();

        foreach ($admins as $admin) {
            checkSection($admin->id);
            echo $admin->name . "<br>\n";
            for ($i = 1; $i <= 2; $i++) {
                list($contents, $total) = Content::readContents(null, null, $admin->id, $i);

                $admins_ids = explode(',', join(',', $contents->where('admins_ids', '!=', null)->pluck('admins_ids')->toArray()));

                if ($i != 1 && in_array($admin->id, $admins_ids) && !ContentTypeAccess::where('type', $i)->where('admin_id', $admin->id)->first()) {
                    ContentTypeAccess::create([
                        'type' => $i,
                        'admin_id' => $admin->id
                    ]);
                } else if (in_array($admin->id, $admins_ids) && stripos($admin->level, 'contents') === false) {
                    $admin->level = $admin->level . ',contents';
                    $admin->save();
                }
            }
        }
    }
}
