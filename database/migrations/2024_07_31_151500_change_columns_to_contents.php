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
        Schema::table('contents', function (Blueprint $table) {
            $table->string('admins_ids', 2000)->change();
            $table->string('properties_ids', 2000)->change();
            $table->string('access_level', 2000)->change();
            $table->string('cities', 2000)->change();
            $table->string('states', 2000)->change();
            $table->string('countries', 2000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            //
        });
    }
};
