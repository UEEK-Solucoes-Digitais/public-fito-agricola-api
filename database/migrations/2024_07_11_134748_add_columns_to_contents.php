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
            $table->tinyInteger('is_course')->default(0)->after('date');
            $table->string('duration_time')->default('')->after('date');
            $table->string('cities')->nullable()->after('date');
            $table->string('access_level')->nullable()->after('date');
            $table->string('admins_ids')->nullable()->after('date');
            $table->string('properties_ids')->nullable()->after('date');
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
