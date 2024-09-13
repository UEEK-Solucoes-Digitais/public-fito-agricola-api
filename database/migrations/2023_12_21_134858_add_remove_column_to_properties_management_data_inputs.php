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
        Schema::table('properties_management_data_inputs', function (Blueprint $table) {
            $table->dropForeign('properties_management_data_inputs_stock_incoming_id_foreign');
            $table->dropColumn('stock_incoming_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id', 'pmdi_pi')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_management_data_inputs', function (Blueprint $table) {
            //
        });
    }
};
