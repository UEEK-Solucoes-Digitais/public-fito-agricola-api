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
        Schema::table('financial_movimentations', function (Blueprint $table) {
            $table->tinyInteger('subtype')->default(0)->comment("1 -> Fornecedor | 2 -> Funcionário | 3 -> Impostos")->after('is_conciliated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_movimentations', function (Blueprint $table) {
            //
        });
    }
};
