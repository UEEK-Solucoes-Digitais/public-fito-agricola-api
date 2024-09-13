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
            $table->decimal("dosage_per_hectare", 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_management_data_inputs', function (Blueprint $table) {
            $table->dropColumn("dosage_per_hectare");
        });
    }
};
