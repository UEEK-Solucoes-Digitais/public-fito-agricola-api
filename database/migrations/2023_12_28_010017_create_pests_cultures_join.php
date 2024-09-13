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
        Schema::create('pests_cultures_join', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pest_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('pest_id')->references('id')->on('interference_factors_items');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pests_diseases_join');
    }
};
