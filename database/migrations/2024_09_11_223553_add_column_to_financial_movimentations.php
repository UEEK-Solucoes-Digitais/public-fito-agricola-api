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
            $table->tinyInteger('is_conciliated')->default(0)->after('period');
        });

        Schema::table('financial_movimentation_charges', function (Blueprint $table) {
            $table->tinyInteger('is_conciliated')->default(0)->after('installment');
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
