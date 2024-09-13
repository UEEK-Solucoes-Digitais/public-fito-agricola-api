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
        Schema::create('stock_exits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('properties_crops_id');
            $table->unsignedBigInteger('stock_incoming_id')->comment("id do item de estoque relacionado ao produto");
            $table->integer("quantity")->comment("quantidade do produto utilizado");
            $table->timestamps();
            $table->foreign('properties_crops_id')->references('id')->on('properties_crops_join');
            $table->foreign('stock_incoming_id')->references('id')->on('stock_incomings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_stock_exits');
    }
};
