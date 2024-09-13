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
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('quantity');
            $table->dropColumn('quantity_unity');
            $table->dropColumn('value');
            $table->unsignedBigInteger('item_id')->comment('cultura | fertilizante | defensivo')->after('type');
        });

        Schema::table('stock_incomings', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->after('value');
            $table->tinyInteger('quantity_unit')->comment(' 1 - KG | 2 - Litros')->after('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            //
        });
    }
};
