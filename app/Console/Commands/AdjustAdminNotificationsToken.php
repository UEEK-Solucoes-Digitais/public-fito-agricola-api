<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\AdminNotificationToken;
use Illuminate\Console\Command;

class AdjustAdminNotificationsToken extends Command
{
    protected $signature = 'app:adjust-admin-notifications-token';

    protected $description = 'Comando para criar o token de notificação na tabela admin_notifications_token a partir do token que já existe na tabela admins';

    public function handle()
    {
        $this->info('Iniciando o processo de ajuste dos tokens de notificação dos administradores...');

        $this->info('Obtendo os administradores com tokens de notificação...');

        $admins = Admin::where('status', 1)->get();

        $this->info('Ajustando os tokens de notificação dos administradores...');


        foreach ($admins as $admin) {
            checkSection($admin->id);
            $this->info('Ajustando o token de notificação do administrador ' . $admin->name . '...');

            if ($admin->notifications_token) {
                $this->info('Token de notificação encontrado: ' . $admin->notifications_token);

                $this->info('Criando o token de notificação na tabela admin_notifications_token...');

                $admin_notification_token = new AdminNotificationToken();
                $admin_notification_token->token = $admin->notifications_token;
                $admin_notification_token->admin_id = $admin->id;
                $admin_notification_token->device_id = '';
                $admin_notification_token->save();

                $this->info('Token de notificação criado com sucesso!');
            } else {
                $this->info('Token de notificação não encontrado!');
            }
        }

        $this->info('Processo de ajuste dos tokens de notificação dos administradores finalizado!');
    }
}
