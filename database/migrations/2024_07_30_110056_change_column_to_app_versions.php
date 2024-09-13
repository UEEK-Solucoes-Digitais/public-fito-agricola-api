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
        Schema::table('app_versions', function (Blueprint $table) {
            $table->dropColumn('android_version');
            $table->dropColumn('ios_version');
        });
        Schema::table('app_versions', function (Blueprint $table) {
            $table->tinyInteger('android_version')->default(2);
            $table->tinyInteger('ios_version')->default(2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            //
        });
    }
};
