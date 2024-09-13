<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 155);
            $table->string('email', 155);
            $table->string('password', 255);
            $table->string('cpf', 14)->default('')->nullable();
            $table->string('phone', 15)->default('')->nullable();
            $table->bigInteger('access_level')->default(1)->comment('Permissões de usuários: Todos os módulos do sistema + a função “Editar histórico”');
            $table->tinyInteger('level')->comment('1 => Administrador // 2 => Produtor // 3 => Consultores // 4 => M.A // 5 => Equipe');
            $table->timestamps();
            $table->tinyInteger('status')->default(1)->comment('0 => Excluído // 1 => Ativo // 2 => Inativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
