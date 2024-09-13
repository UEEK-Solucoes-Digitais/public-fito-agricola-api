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
        Schema::table('properties_crops_join', function (Blueprint $table) {
            $table->tinyInteger('is_harvested')->comment("se a lavoura está colhida nessa safra")->default(0)->after('subharvest_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_crops_join', function (Blueprint $table) {
            //
        });
    }
};
