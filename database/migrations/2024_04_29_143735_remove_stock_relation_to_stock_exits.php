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
        Schema::table('stock_exits', function (Blueprint $table) {
            $table->dropForeign(['stock_incoming_id']);
            $table->dropColumn('stock_incoming_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_exits', function (Blueprint $table) {
            //
        });
    }
};
