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
        // sementes no dado de manejo
        Schema::create('properties_management_data_seeds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_incoming_id')->comment("id do item de estoque relacionado ao produto");
            $table->unsignedBigInteger('properties_crops_id');
            $table->decimal('cost_per_kilogram', 10, 2);
            $table->decimal('kilogram_per_ha', 10, 2);
            $table->decimal('spacing', 10, 2);
            $table->decimal('seed_per_linear_meter', 10, 2);
            $table->decimal('seed_per_square_meter', 10, 2);
            $table->decimal('pms', 10, 2);
            $table->decimal('quantity_per_ha', 10, 2);
            $table->timestamps();
            $table->foreign('stock_incoming_id', "pmds_si_id")->references('id')->on('stock_incomings');
            $table->foreign('properties_crops_id', "pmds_pc_id")->references('id')->on('properties_crops_join');
        });

        // população no dado de manejo
        Schema::create('properties_management_data_population', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('properties_crops_id');
            $table->unsignedBigInteger('culture_id');
            $table->decimal('cost_per_kilogram', 10, 2);
            $table->decimal('cost_per_ha', 10, 2);
            $table->decimal('spacing', 10, 2);
            $table->decimal('seed_per_linear_meter', 10, 2);
            $table->decimal('seed_per_square_meter', 10, 2)->comment("planta por metro linear divido pelo espaçamento da semente vinculada");
            $table->decimal('pms', 10, 2);
            $table->decimal('quantity_per_ha', 10, 2);
            $table->timestamps();
            $table->foreign('properties_crops_id', "pmdp_pc_id")->references('id')->on('properties_crops_join');
            $table->foreign('culture_id')->references('id')->on('cultures');
        });

        // fertilizantes e defensivos no dado de manejo
        Schema::create('properties_management_data_inputs', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->comment("1 => fertilizante | 2 => defensivo");
            $table->date('date');
            $table->decimal('dosage', 10, 2)->comment("dose por hectare");
            $table->unsignedBigInteger('properties_crops_id');
            $table->unsignedBigInteger('stock_incoming_id')->comment("id do item de estoque relacionado ao produto");
            $table->timestamps();
            $table->foreign('properties_crops_id', "pmdi_pc_id")->references('id')->on('properties_crops_join');
            $table->foreign('stock_incoming_id')->references('id')->on('stock_incomings');
        });

        // colheita no dado de manejo
        Schema::create('properties_management_data_harvest', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('properties_crops_id');
            $table->date('date');
            $table->decimal('total_production', 10, 2);
            $table->decimal('productivity', 10, 2)->comment("produção total dividido pela área da lavoura vinculada");
            $table->foreign('properties_crops_id', "pmdh_pc_id")->references('id')->on('properties_crops_join');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_management_data_seeds');
        Schema::dropIfExists('properties_management_data_population');
        Schema::dropIfExists('properties_management_data_inputs');
        Schema::dropIfExists('properties_management_data_harvest');
    }
};
