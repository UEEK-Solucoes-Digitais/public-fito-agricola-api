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
            $table->dropForeign('financial_injection_bank_account_management_id_foreign');
            $table->dropColumn('bank_account_management_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_injections', function (Blueprint $table) {
            //
        });
    }
};
