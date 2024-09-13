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
        Schema::create('content_types_access', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->comment("2 -> M.A");
            $table->unsignedBigInteger('admin_id');
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_types_access');
    }
};
