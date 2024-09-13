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
            $table->unsignedBigInteger('supplier_management_id')->nullable()->after('client_management_id');
            $table->foreign('supplier_management_id')->references('id')->on('supplier_management');
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
