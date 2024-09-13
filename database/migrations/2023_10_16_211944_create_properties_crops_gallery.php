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
        // galeria de imagens dos monitoramentos
        Schema::create('properties_crops_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id');
            $table->tinyInteger('type')->comment("1 => estádio // 2 => doenças // 3 => praga // 4 => daninha // 5 => observação");
            $table->string('image', 255);
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_crops_gallery');
    }
};
