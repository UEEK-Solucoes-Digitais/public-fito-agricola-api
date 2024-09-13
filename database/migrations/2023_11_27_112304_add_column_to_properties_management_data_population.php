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
        Schema::table('properties_management_data_population', function (Blueprint $table) {
            $table->tinyInteger('emergency_percentage')->nullable();
            $table->date('emergency_percentage_date')->nullable();
            $table->unsignedBigInteger('property_management_data_seed_id')->comment("necessário para fazer o calculo do espaçamento da semente vinculada");
            $table->foreign('property_management_data_seed_id', "pmdp_pmds_id")->references('id')->on('properties_management_data_seeds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_management_data_population', function (Blueprint $table) {
            //
        });
    }
};
