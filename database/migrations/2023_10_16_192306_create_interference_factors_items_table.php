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
        Schema::create('interference_factors_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('scientific_name', 255);
            $table->text('observation')->nullable();
            $table->tinyInteger('type')->comment('1 => weeds // 2 => diseases // 3 => pests');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interference_factors_tables');
    }
};
