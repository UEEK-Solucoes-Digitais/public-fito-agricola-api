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
        Schema::table('properties_management_data_harvest', function (Blueprint $table) {
            $table->unsignedBigInteger('property_management_data_seed_id')->nullable()->after('productivity');
            $table->foreign('property_management_data_seed_id', "pmdp_pmds2_id")->references('id')->on('properties_management_data_seeds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_management_data_harvest', function (Blueprint $table) {
            //
        });
    }
};
