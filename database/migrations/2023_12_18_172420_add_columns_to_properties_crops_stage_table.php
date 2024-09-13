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
        Schema::table('properties_crops_stage', function (Blueprint $table) {
            $table->string('vegetative_age_text', 150)->nullable();
            $table->string('reprodutive_age_text', 150)->nullable();

            $table->dropColumn('vegetative_age_period');
            $table->dropColumn('reprodutive_age_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties_crops_stage', function (Blueprint $table) {
            $table->dropColumn('vegetative_age_text');
            $table->dropColumn('reprodutive_age_text');

            $table->tinyInteger('vegetative_age_period')->comment('1 => dia // 2 => semanas // 3 => meses // 4 => anos');
            $table->tinyInteger('reprodutive_age_period')->comment('1 => dia // 2 => semanas // 3 => meses // 4 => anos');
        });
    }
};
