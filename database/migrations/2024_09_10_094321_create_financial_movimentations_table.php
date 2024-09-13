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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->comment("1 -> Despesas administrativas / 2 -> Despesas bancárias");
            $table->timestamps();
        });

        Schema::create('financial_tax_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('financial_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('financial_movimentations', function (Blueprint $table) {
            $table->id();
            $table->string('sale_title')->nullable()->comment('Título da venda, caso seja');
            $table->string("bill_file")->nullable()->comment("Arquivo de boleto caso haja");
            $table->string("bill_number", 1000)->nullable()->comment("Linha de boleto caso haja");
            $table->text('observations')->nullable();
            $table->decimal("total_value", 12, 2);
            $table->date('date');
            $table->date("first_due_date");
            $table->date("last_due_date")->nullable();
            $table->tinyInteger('type')->comment('1->venda / 2->despesa / 3 -> outros recebimentos');
            $table->tinyInteger('payment_type')->comment("1 -> A vista / 2 -> Parcelado / 3 -> recorrente");
            $table->tinyInteger('conditions')->comment("parcelamento. de 1 a 64x");
            $table->tinyInteger('period')->comment("1-> Diário | 2 -> Semanal | 3 -> Mensal | 4 -> Semestral | 5 -> Anual");
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('client_management_id')->nullable();
            $table->unsignedBigInteger('financial_category_id')->nullable();
            $table->unsignedBigInteger('financial_tax_type_id')->nullable();
            $table->unsignedBigInteger('bank_account_management_id')->nullable();
            $table->unsignedBigInteger('financial_payment_method_id');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins');
            $table->foreign('client_management_id')->references('id')->on('client_management');
            $table->foreign('financial_category_id')->references('id')->on('financial_categories');
            $table->foreign('financial_tax_type_id')->references('id')->on('financial_tax_types');
            $table->foreign('financial_payment_method_id')->references('id')->on('financial_payment_methods');
            $table->foreign('bank_account_management_id')->references('id')->on('bank_account_management');
        });

        Schema::create('financial_movimentation_charges', function (Blueprint $table) {
            $table->id();
            $table->date('due_date');
            $table->date('income_date')->comment("data de faturamento");
            $table->decimal("value", 12, 2);
            $table->unsignedBigInteger('financial_payment_method_id');
            $table->unsignedBigInteger('financial_movimentation_id');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->foreign('financial_movimentation_id', 'fmc_fmid')->references('id')->on('financial_movimentations');
            $table->foreign('financial_payment_method_id', 'fmc_fpmid')->references('id')->on('financial_payment_methods');
        });

        Schema::create('financial_injection', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('investor')->comment();
            $table->decimal("value", 12, 2);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_movimentation_charges');
        Schema::dropIfExists('financial_movimentations');
        Schema::dropIfExists('financial_payment_methods');
        Schema::dropIfExists('financial_tax_types');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('financial_injection');
    }
};
