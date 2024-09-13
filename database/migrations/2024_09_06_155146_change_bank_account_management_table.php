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

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('image', 255);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::table('bank_account_management', function (Blueprint $table) {
            $table->dropColumn('bank');
            $table->unsignedBigInteger('bank_id');
            $table->foreign('bank_id')->references('id')->on('banks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
