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
        Schema::table('properties_management_data_seeds', function (Blueprint $table) {
            $table->dropForeign('pmds_si_id');
            $table->dropColumn('stock_incoming_id');
            $table->dropColumn('dosage');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id', 'pmds_pid')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_management_data_seeds', function (Blueprint $table) {
            //
        });
    }
};
