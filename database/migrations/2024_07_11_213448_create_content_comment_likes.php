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
        Schema::create('content_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_comment_id')->nullable();
            $table->unsignedBigInteger('admin_id');
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->foreign('content_comment_id')->references('id')->on('content_comments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_comment_likes');
    }
};
