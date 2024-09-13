<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropFileSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $crop_file = new \App\Models\CropFile();
            $crop_file->name = "Arquivo $i";
            $crop_file->path = "path/to/file/$i";
            $crop_file->unit_p = rand(1, 100);
            $crop_file->unit_k = rand(1, 100);
            $crop_file->unit_al = rand(1, 100);
            $crop_file->unit_mg = rand(1, 100);
            $crop_file->unit_ca = rand(1, 100);
            $crop_file->base_saturation = rand(1, 100);
            $crop_file->organic_material = rand(1, 100);
            $crop_file->clay = rand(1, 100);
            $crop_file->status = 1;
            $crop_file->crop_id = rand(1, 100);
            $crop_file->save();
        }
    }
}
