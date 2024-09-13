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
        Schema::table('properties_crops_diseases', function (Blueprint $table) {
            $table->unsignedBigInteger("admin_id")->default(1)->nullable();
            $table->foreign("admin_id")->references("id")->on("admins");
        });

        Schema::table('properties_crops_observations', function (Blueprint $table) {
            $table->unsignedBigInteger("admin_id")->default(1)->nullable();
            $table->foreign("admin_id")->references("id")->on("admins");
        });

        Schema::table('properties_crops_pests', function (Blueprint $table) {
            $table->unsignedBigInteger("admin_id")->default(1)->nullable();
            $table->foreign("admin_id")->references("id")->on("admins");
        });

        Schema::table('properties_crops_stage', function (Blueprint $table) {
            $table->unsignedBigInteger("admin_id")->default(1)->nullable();
            $table->foreign("admin_id")->references("id")->on("admins");
        });

        Schema::table('properties_crops_weeds', function (Blueprint $table) {
            $table->unsignedBigInteger("admin_id")->default(1)->nullable();
            $table->foreign("admin_id")->references("id")->on("admins");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }
};
