<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\FirebaseHelperController;
use App\Models\Admin;
use Illuminate\Console\Command;

class SendDefaultNotification extends Command
{
    protected $signature = 'app:send-default-notification';

    protected $description = 'Enviar notificação padrão para todos os usuários';

    public function handle()
    {
        $admins = Admin::where('status', 1)->where('notifications_token', '!=', '')->get();
        $title = "Nova atualização obrigatória";
        $text = "Atualize o aplicativo na loja do seu dispositivo para continuar utilizando";

        foreach ($admins as $admin) {
            // linha abaixo filtra somente pro usuario UEEK
            // if ($admin->id == 1) {
            foreach ($admin->tokens as $token) {
                echo $admin->name . "\n";
                FirebaseHelperController::SendGenericNotification($token->token, $title, $text);
            }
            // }
        }
    }
}
