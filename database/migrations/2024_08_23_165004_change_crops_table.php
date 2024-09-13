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
        Schema::table('crops', function (Blueprint $table) {
            $table->decimal('clay', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('organic_material', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('base_saturation', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('unit_ca', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('unit_mg', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('unit_al', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('unit_k', 3, 1)->after('kml_file')->nullable()->default(0.0);
            $table->decimal('unit_p', 3, 1)->after('kml_file')->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
