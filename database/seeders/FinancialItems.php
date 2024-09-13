<?php

namespace Database\Seeders;

use App\Models\FinancialPaymentMethod;
use App\Models\FinancialTaxType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinancialItems extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = ['Cartão de crédito', 'Cartão de débito', "Boleto bancário", "Dinheiro", "Transferência bancária", "PIX", "Cheque"];
        checkSection(1);

        foreach ($methods as $method) {
            FinancialPaymentMethod::create([
                'name' => $method
            ]);
        }

        $tax = ['PIS', 'COFINS', 'ISSQN', 'IRPJ', 'CSLL', 'ITR'];

        foreach ($tax as $tax) {
            FinancialTaxType::create([
                'name' => $tax
            ]);
        }
    }
}
