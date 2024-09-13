<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{

    public function run(): void
    {
        // senha ja tem um cast de hash no model
        User::create([
            'email' => 'apiadmin@admin.com',
            'password' => "",
        ]);
    }
}
