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
        // monitoramento de observações
        Schema::create('properties_crops_observations', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('risk')->comment('1 => Sem risco // 2 => Exige atenção // 3 => Requer urgência');
            $table->text('observations');
            $table->point("coordinates")->nullable();
            $table->string("kml_file", 255)->nullable();
            $table->unsignedBigInteger('properties_crops_id');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_crops_observations');
    }
};
