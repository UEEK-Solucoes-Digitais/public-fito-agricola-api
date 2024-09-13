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
        // pluviômetro
        Schema::create('properties_crops_rain_gauge', function (Blueprint $table) {
            $table->id();
            $table->integer('volume');
            $table->date('date');
            $table->unsignedBigInteger('properties_crops_id');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('properties_crops_id')->references('id')->on('properties_crops_join');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_crops_rain_gauge');
    }
};
