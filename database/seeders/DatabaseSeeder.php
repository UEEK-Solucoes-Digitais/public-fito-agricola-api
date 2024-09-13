<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        checkSection(1);
        $this->call(AdminSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FinancialItems::class);
    }
}
