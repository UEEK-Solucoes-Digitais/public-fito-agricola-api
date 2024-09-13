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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->tinyInteger('type');
            $table->integer('quantity')->comment('pode ser negativo');
            $table->tinyInteger('quantity_unity');
            $table->decimal('value', 9, 2);
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::create('stock_incomings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->integer('quantity');
            $table->decimal('value', 9, 2);
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
