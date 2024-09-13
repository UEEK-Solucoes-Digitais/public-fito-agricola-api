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
        // monitoramento de estádio
        Schema::create('properties_crops_stage', function (Blueprint $table) {
            $table->id();
            $table->integer('vegetative_age_value');
            $table->tinyInteger('vegetative_age_period')->comment('1 => dia // 2 => semanas // 3 => meses // 4 => anos');
            $table->integer('reprodutive_age_value');
            $table->tinyInteger('reprodutive_age_period')->comment('1 => dia // 2 => semanas // 3 => meses // 4 => anos');
            $table->tinyInteger('risk')->comment('1 => Sem risco // 2 => Exige atenção // 3 => Requer urgência');
            $table->point("coordinates")->nullable();
            $table->string("kml_file", 255)->nullable();
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
        Schema::dropIfExists('properties_crops_stage');
    }
};
