<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Suporte',
            'email' => 'suporte@admin.com',
            'password' => Hash::make('cWB9Ynn9Aw76*'),
            'access_level' => 1,
            'level' => 'admins,costs,contents,properties,interference_factors,inputs,crops,stocks,assets,reports,harvests',
        ]);
    }
}
