<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PropertyManagementDataInput;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CorrectDuplicatedProductsImport implements ToCollection
{

    public function collection(Collection $collection)
    {
        ini_set('memory_limit', '-1');

        checkSection(1);

        // $products_to_change = [];

        foreach ($collection as $key => $row) {
            if ($key === 0) continue;


            $action = $row[3];

            if (strtolower($action) == "remover") {
                $product_id = $row[0];
                $product_name = $row[1];
                $product_old = Product::where('id', $product_id)->first();
                $product_equal = Product::where('name', $product_name)->where('id', '!=', $product_id)->where('type', $product_old->type)->first();

                if ($product_equal) {
                    echo "Removendo produto duplicado - {$product_name} (tipo: $product_old->type) ({$product_id}) para {$product_equal->id} (tipo: $product_equal->type) \n\n";
                    Stock::where('product_id', $product_id)->update(['product_id' => $product_equal->id]);
                    PropertyManagementDataInput::where('product_id', $product_id)->update(['product_id' => $product_equal->id]);

                    Product::where('id', $product_id)->update(['status' => 0]);
                    // $products_to_change[] = [
                    //     "id_wrong" => $product_id,
                    //     "type_wrong" => $row[2],
                    //     "id_correct" => $product_equal->id,
                    // ];
                }
            }
        }

        // dd($products_to_change);
    }
}
