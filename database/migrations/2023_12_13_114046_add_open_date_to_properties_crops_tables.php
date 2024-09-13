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
            $table->date('open_date')->default(null);
        });

        Schema::table('properties_crops_observations', function (Blueprint $table) {
            $table->date('open_date')->default(null);
        });

        Schema::table('properties_crops_pests', function (Blueprint $table) {
            $table->date('open_date')->default(null);
        });

        Schema::table('properties_crops_stage', function (Blueprint $table) {
            $table->date('open_date')->default(null);
        });

        Schema::table('properties_crops_weeds', function (Blueprint $table) {
            $table->date('open_date')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_crops_diseases', function (Blueprint $table) {
            $table->dropColumn('open_date');
        });

        Schema::table('properties_crops_observations', function (Blueprint $table) {
            $table->dropColumn('open_date');
        });

        Schema::table('properties_crops_pests', function (Blueprint $table) {
            $table->dropColumn('open_date');
        });

        Schema::table('properties_crops_stage', function (Blueprint $table) {
            $table->dropColumn('open_date');
        });

        Schema::table('properties_crops_weeds', function (Blueprint $table) {
            $table->dropColumn('open_date');
        });
    }
};
