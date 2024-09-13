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
            $table->tinyInteger('status')->default(1);
        });
        Schema::table('properties_management_data_population', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1);
        });
        Schema::table('properties_management_data_inputs', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1);
        });
        Schema::table('properties_management_data_harvest', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('management_data', function (Blueprint $table) {
            //
        });
    }
};
