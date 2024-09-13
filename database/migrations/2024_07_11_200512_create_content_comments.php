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
        Schema::create('content_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->text('text');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->foreign('content_id')->references('id')->on('contents');
            $table->foreign('answer_id')->references('id')->on('content_comments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_comments_page');
    }
};
