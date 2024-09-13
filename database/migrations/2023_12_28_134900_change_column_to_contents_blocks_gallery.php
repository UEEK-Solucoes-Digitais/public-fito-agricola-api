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
        Schema::table('contents_blocks_gallery', function (Blueprint $table) {
            $table->dropForeign("contents_blocks_gallery_content_block_id_foreign");
            $table->foreign("content_block_id")->references("id")->on("contents_blocks");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents_blocks_gallery', function (Blueprint $table) {
            //
        });
    }
};
