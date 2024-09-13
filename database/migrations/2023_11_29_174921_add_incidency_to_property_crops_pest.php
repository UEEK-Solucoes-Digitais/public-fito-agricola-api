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
        Schema::table('properties_crops_pests', function (Blueprint $table) {
            $table->decimal('incidency', 8, 2)->default(0)->nullable()->change();
        });

        Schema::table('properties_crops_diseases', function (Blueprint $table) {
            $table->decimal('incidency', 8, 2)->default(0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_crops_pest', function (Blueprint $table) {
            //
        });
    }
};
