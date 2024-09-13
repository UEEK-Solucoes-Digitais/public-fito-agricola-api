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
            $table->dropColumn('clay');
            $table->dropColumn('organic_material');
            $table->dropColumn('base_saturation');
            $table->dropColumn('unit_ca');
            $table->dropColumn('unit_mg');
            $table->dropColumn('unit_al');
            $table->dropColumn('unit_k');
            $table->dropColumn('unit_p');
        });

        Schema::table('crops_files', function (Blueprint $table) {
            $table->decimal('clay', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('organic_material', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('base_saturation', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('unit_ca', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('unit_mg', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('unit_al', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('unit_k', 6, 2)->after('path')->nullable()->default(0.0);
            $table->decimal('unit_p', 6, 2)->after('path')->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            //
        });
    }
};
