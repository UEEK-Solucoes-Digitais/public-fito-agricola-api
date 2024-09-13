<?php

namespace App\Imports;

use App\Models\DiseaseCultureJoin;
use App\Models\InterferenceFactorItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportProducts implements ToCollection
{

    public function collection(Collection $collection)
    {
        try {
            ini_set('memory_limit', '-1');

            foreach ($collection as $key => $row) {
                if ($key === 0) continue;

                if ($row[0] && $row[2]) {

                    checkSection(1);

                    $disease_name = $row[0];
                    $disease_scientific_name = $row[1] ?? "";
                    $cultures = explode('/', $row[2]);

                    $disease = InterferenceFactorItem::where('name', $disease_name)->where('scientific_name', $disease_scientific_name)->where("status", 1)->first();

                    if (!$disease) {
                        $disease = InterferenceFactorItem::create([
                            'name' => $disease_name,
                            'scientific_name' => $disease_scientific_name,
                            'type' => 2,
                            'status' => 1
                        ]);
                    }

                    foreach ($cultures as $culture) {
                        $culture = Product::where('name', $culture)->where("status", 1)->first();

                        if (!$culture) {
                            $culture = Product::create([
                                'name' => $culture,
                                'extra_column' => '',
                                'admin_id' => 1,
                                'type' => 1,
                                'status' => 1
                            ]);
                        }

                        DiseaseCultureJoin::create([
                            'disease_id' => $disease->id,
                            'product_id' => $culture->id,
                            'status' => 1
                        ]);
                    }
                }
            }
            // dd([$this->total_plants_found, $this->total_diseases_found, $this->total_registered_infos]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Artisan::call('app:add-products');
        }
    }
}
