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
        Schema::create('financial_transfers', function (Blueprint $table) {
            $table->id();
            $table->decimal("value", 10, 2);
            $table->date("date");
            $table->text("observations")->default('');
            $table->string("external_account_agency")->nullable();
            $table->string("external_account_account")->nullable();
            $table->unsignedBigInteger('origin_bank_account_id');
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('destiny_bank_account_id')->nullable();
            $table->unsignedBigInteger("external_account_bank_id")->nullable();
            $table->tinyInteger("type")->comment('1 -> TED | 2 -> DOC | 3 -> PIX | 4 -> Cheque');
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->foreign('origin_bank_account_id')->references('id')->on('bank_account_management');
            $table->foreign('destiny_bank_account_id')->references('id')->on('bank_account_management');
            $table->foreign('external_account_bank_id')->references('id')->on('banks');
        });

        Schema::create('financial_transfer_files', function (Blueprint $table) {
            $table->id();
            $table->string("file");
            $table->unsignedBigInteger("financial_transfer_id");
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
            $table->foreign('financial_transfer_id')->references('id')->on('financial_transfers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transfers');
    }
};
