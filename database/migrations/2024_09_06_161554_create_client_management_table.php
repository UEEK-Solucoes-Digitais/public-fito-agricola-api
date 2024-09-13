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
        Schema::create('client_management', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->tinyInteger('seller');
            $table->string('email', 255);
            $table->string('phone', 15);
            $table->tinyInteger('type'); //1 - Pessoa física // 2 - Pessoa jurídica
            $table->string('document', 255);
            $table->string('state_registration', 255);
            $table->string('branch_of_activity', 255);
            $table->string('cep', 255);
            $table->string('country', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->bigInteger('number');
            $table->string('street', 255);
            $table->string('complement', 255);
            $table->string('reference', 255);
            
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_management');
    }
};
