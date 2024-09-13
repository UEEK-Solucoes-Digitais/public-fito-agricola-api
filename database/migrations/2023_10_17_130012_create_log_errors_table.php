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
        Schema::create('log_errors', function (Blueprint $table) {
            $table->id();
            $table->string("error_description", 100);
            $table->tinyInteger("environment");
            $table->string("table_name", 50);
            $table->text("exception_message");
            $table->text("exception_file");
            $table->string("exception_line", 10);
            $table->dateTime("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_log');
    }
};
