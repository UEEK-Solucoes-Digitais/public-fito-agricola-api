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
        Schema::create('bank_account_management', function (Blueprint $table) {
            $table->id();

            $table->string('bank', 255);
            $table->string('agency', 20);
            $table->string('account', 20);
            $table->decimal('start_balance', 10, 2);
            $table->date('start_date');
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
        Schema::dropIfExists('bank_account_management');
    }
};
