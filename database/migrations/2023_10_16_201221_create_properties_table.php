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

        // propriedades
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('cep', 9);
            $table->string('uf', 255);
            $table->string('city', 255);
            $table->string('street', 255);
            $table->string('neighborhood', 255);
            $table->integer('number');
            $table->string('complement', 500);
            $table->string('state_subscription', 100);
            $table->point("coordinates")->nullable();
            $table->unsignedBigInteger('admin_id')->comment('Id do proprietário');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('admin_id')->references('id')->on('admins');
        });

        // Schema::create('properties_harvests_join', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('property_id');
        //     $table->unsignedBigInteger('harvest_id');
        //     $table->timestamps();
        //     $table->foreign('property_id')->references('id')->on('properties');
        //     $table->foreign('harvest_id')->references('id')->on('harvests');
        // });

        // vinculo de propriedades com lavouras e safras
        Schema::create('properties_crops_join', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('harvest_id');
            $table->unsignedBigInteger('crop_id');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('harvest_id')->references('id')->on('harvests');
            $table->foreign('crop_id')->references('id')->on('crops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
