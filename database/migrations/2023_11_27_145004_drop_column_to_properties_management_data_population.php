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
            $table->dropColumn("cost_per_kilogram");
            $table->dropColumn("cost_per_ha");
            $table->dropColumn("pms");
            $table->dropForeign("properties_management_data_population_culture_id_foreign");
            $table->dropColumn("culture_id");
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
