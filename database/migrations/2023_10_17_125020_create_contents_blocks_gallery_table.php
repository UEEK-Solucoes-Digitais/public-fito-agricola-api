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
        Schema::create('contents_blocks_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_block_id');
            $table->string('image', 255);
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('content_block_id')->references('id')->on('contents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents_blocks_gallery');
    }
};
