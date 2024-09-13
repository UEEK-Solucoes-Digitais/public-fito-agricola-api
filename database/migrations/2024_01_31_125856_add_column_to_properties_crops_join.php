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
            $table->tinyInteger('is_subharvest')->default(0)->comment("1 -> subsafra");
            $table->string("subharvest_name")->nullable()->comment("Nome da subsafra");
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
