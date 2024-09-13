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
        Schema::table('log_system', function (Blueprint $table) {
            $table->string('from', 5000)->default('')->comment('Estado inicial, vazio caso seja edição ou exclusão')->change();
            $table->string('to', 5000)->default('')->comment('Estado final')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_system', function (Blueprint $table) {
            //
        });
    }
};
