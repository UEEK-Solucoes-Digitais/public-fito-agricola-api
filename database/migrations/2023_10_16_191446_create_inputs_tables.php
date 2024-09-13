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
        Schema::create('cultures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->timestamps();
            $table->tinyInteger('status')->default(1)->comment('0 => excluido // 1 => ativo // 2 => inativo)');
        });

        Schema::create('defensives', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->tinyInteger('type');
            $table->text('observation');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
        });

        Schema::create('fertilizers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('observation');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cultures');
        Schema::dropIfExists('defensives');
        Schema::dropIfExists('fertilizers');
    }
};
