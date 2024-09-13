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
        Schema::table('properties_crops_rain_gauge', function (Blueprint $table) {
            $table->decimal('volume', 8, 2)->default(0)->comment('Volume de chuva')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_crops_rain_gauge', function (Blueprint $table) {
            //
        });
    }
};
