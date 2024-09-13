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
        Schema::create('contents_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_id');
            $table->tinyInteger('type')->comment('1 => texto // 2 => imagem // 3 => galeria // 4 => vídeo (youtube ou vimeo) // 5 => audio (soundcloud ou spotify)');
            $table->string('content', 2000);
            $table->bigInteger('position')->default(999);
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('content_id')->references('id')->on('contents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents_blocks');
    }
};
