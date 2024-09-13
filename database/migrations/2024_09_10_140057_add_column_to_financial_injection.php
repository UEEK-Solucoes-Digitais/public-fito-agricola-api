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
        Schema::table('financial_injection', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_management_id')->nullable();
            $table->foreign('bank_account_management_id')->references('id')->on('bank_account_management');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_injection', function (Blueprint $table) {
            //
        });
    }
};
