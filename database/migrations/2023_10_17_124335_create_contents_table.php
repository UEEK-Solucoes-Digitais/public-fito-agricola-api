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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('admin_id')->comment('id do autor');
            $table->date('date')->nullable()->comment('Caso não específicado, utiliza o created_at');
            $table->timestamps();
            $table->tinyInteger('status')->default(1)->comment('0 => excluído // 1 => publicado // 2 => rascunho');
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->foreign('category_id')->references('id')->on('contents_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
