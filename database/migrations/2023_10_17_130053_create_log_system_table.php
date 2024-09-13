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
        Schema::create('log_system', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('table_name', 100);
            $table->tinyInteger('operation')->comment('1 => Adição // 2 => Edição // 3 => Exclusão');
            $table->unsignedBigInteger('object_id')->nullabe()->comment('Caso edição ou exclusão, traz o id do objeto alterado');
            $table->string('from', 2000)->default('')->comment('Estado inicial, vazio caso seja edição ou exclusão');
            $table->string('to', 2000)->default('')->comment('Estado final');
            $table->dateTime("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_log');
    }
};
