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
        Schema::table('properties_crops_diseases', function (Blueprint $table) {
            $table->foreign('interference_factors_item_id')->references('id')->on('interference_factors_items');
        });
        Schema::table('properties_crops_pests', function (Blueprint $table) {
            $table->foreign('interference_factors_item_id')->references('id')->on('interference_factors_items');
        });
        Schema::table('properties_crops_weeds', function (Blueprint $table) {
            $table->foreign('interference_factors_item_id')->references('id')->on('interference_factors_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_crops', function (Blueprint $table) {
            //
        });
    }
};
