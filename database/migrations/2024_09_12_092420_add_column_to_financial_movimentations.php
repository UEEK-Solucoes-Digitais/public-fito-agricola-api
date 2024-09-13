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
            $table->unsignedBigInteger('people_management_id')->nullable()->after('supplier_management_id');
            $table->foreign('people_management_id')->references('id')->on('people_management');
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
