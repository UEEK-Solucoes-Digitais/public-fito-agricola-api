<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;

use App\Models\Admin;
use App\Models\Stock;
use App\Models\StockIncoming;
use App\Models\Product;
use App\Models\Property;
use App\Models\StockExit;

class StockController extends Controller
{
    public function listProducts($admin_id, Request $request)
    {
        try {
            list($products, $total) = Stock::readStocks($admin_id, $request);

            return response()->json([
                'status' => 200,
                'products' => $products,
                'total' => $total,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listIncomings($admin_id, Request $request, $type = null)
    {
        try {
            list($incomings, $total) = StockIncoming::readIncomings($admin_id,  $request, $type);

            return response()->json([
                'status' => 200,
                'incomings' => $incomings,
                'total' => $total,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listExits($admin_id, Request $request)
    {
        try {
            list($exits, $total) = StockExit::readExits($admin_id,  $request);

            return response()->json([
                'status' => 200,
                'exits' => $exits,
                'total' => $total
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addProduct(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'product_name' => 'required',
                'type' => 'required|min:1',
                // 'object' => 'required|min:1',
                'value' => 'required',
                'quantity' => 'required',
                'quantity_unit' => 'required|min:1',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar estoque', Stock::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->product_id) {
                $product = Product::find($request->product_id);

                if (!$product) {
                    throw new OperationException('Erro ao ler produto na operação de adição de Estoque', Product::getTableName(), "Produto não encontrada: {$request->product_id}", 409);
                }
            } else {
                $admin = Admin::find($request->admin_id);

                $product = new Product();
                $product->name = $request->product_name;
                $product->type = $request->type;
                // $product->item_id = $request->object;
                $product->admin_id = $admin->access_level == 1 ? 0 : $request->admin_id;
                $product->status = $admin->access_level == 1 ? 1 : 2;
                $product->save();
            }

            $stock_to_check = Stock::where('product_id', $product->id)->where('property_id', $request->property_id)->where('product_variant', $request->object)->first();

            $stock = $stock_to_check ?? new Stock();
            $stock->product_variant = $request->object ?? '';
            $stock->product_id = $product->id;

            if ($request->property_id > 0 && $request->property_id != 'null') {
                $stock->property_id = $request->property_id;
            }

            $stock->save();

            $incoming = new StockIncoming();
            $incoming->stock_id = $stock->id;
            $incoming->value = $request->value;
            $incoming->quantity = $request->quantity;
            $incoming->quantity_unit = $request->quantity_unit;
            $incoming->save();

            return response()->json([
                'status' => 200,
                'msg' => "Estoque cadastrado com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function addIncoming(Request $request)
    {
        try {

            checkSection($request->admin_id);


            foreach ($request->quantities as $key => $value) {

                if ($request->alternative_types[$key] && $request->alternative_types[$key] != '0' && $request->alternative_types[$key] != 'null') {
                    $product = Product::where('status', 1)->where('name', $request->product_ids[$key])->where('type', 4)->first();

                    if (!$product) {
                        $product = new Product();
                        $product->name = $request->product_ids[$key];
                        $product->admin_id = $request->admin_id;
                        $product->type = 4;
                        $product->save();
                    }
                } else {
                    $product = Product::find($request->product_ids[$key]);
                }

                $stock = Stock::where('product_id', $product->id)->where('property_id', $request->property_id)->where('status', 1);

                if ($request->culture_codes[$key]) {
                    $stock = $stock->where('product_variant', $request->culture_codes[$key]);
                }

                $stock = $stock->first();

                if (!$stock) {
                    $stock = new Stock();
                    $stock->product_id = $product->id ?? 1;
                    $stock->asset_id = $request->asset_ids[$key] && $request->asset_ids[$key] != 0 ?  $request->asset_ids[$key] : null;
                    $stock->alternative_type = $request->alternative_types[$key] ?? null;
                    $stock->property_id = $request->property_id;
                    $stock->product_variant = $request->culture_codes[$key] ?? '';
                    $stock->save();
                }

                $stock_incoming = new StockIncoming();
                $stock_incoming->stock_id = $stock->id;
                $stock_incoming->quantity = floatval(str_replace(',', '.', str_replace('.', '', $request->quantities[$key])));
                $stock_incoming->value = floatval(str_replace(',', '.', str_replace('.', '', $request->values[$key])));
                $stock_incoming->property_id = $request->property_id;
                $stock_incoming->supplier_name = $request->supplier_name ?? '';
                $stock_incoming->entry_date = $request->entry_date ?? date('Y-m-d');
                $stock_incoming->nfe_number = $request->nfe_number ?? '';
                $stock_incoming->nfe_serie = $request->nfe_serie ?? '';
                // $stock_incoming->quantity_unit = $request->quantity_unit;
                $stock_incoming->save();
            }

            return response()->json([
                'status' => 200,
                'msg' => "Entrada cadastrada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function changeIncoming(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar estoque', StockIncoming::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $incoming = StockIncoming::find($request->id);

            if (!$incoming) {
                throw new OperationException('Erro ao ler entrada na operação de alteração', StockIncoming::getTableName(), "Entrada não encontrada: {$request->id}", 409);
            }

            $stock = Stock::find($incoming->stock_id);

            $product = Product::find($stock->product_id);

            if ($product->type == 4 || ($request->alternative_type != 0 && $request->product_text != "")) {
                if ($product->name != $request->product_text) {
                    if (Stock::where('product_id', $product->id)->where('product_variant', $stock->product_variant)->count() == 1) {
                        $product->name = $request->product_text;
                        $product->save();
                    } else {
                        $product = Product::where('status', 1)->where('name', $request->product_text)->where('type', 4)->first();

                        if (!$product) {
                            $product = new Product();
                            $product->name = $request->product_text;
                            $product->admin_id = $request->admin_id;
                            $product->type = 4;
                            $product->save();
                        }
                    }
                }
                $stock->alternative_type = $request->alternative_type ?? null;
                $stock->product_variant =  '';
            } else {
                $product = Product::find($request->product_id);
                $stock->alternative_type = 0;
                $stock->product_variant = $request->culture_code ?? '';
            }

            $stock->product_id = $product->id;
            $stock->asset_id = $request->asset_id ?? null;
            $stock->save();

            $incoming->supplier_name = $request->supplier_name ?? '';
            $incoming->entry_date = $request->entry_date ?? date('Y-m-d');
            $incoming->nfe_number = $request->nfe_number ?? '';
            $incoming->nfe_serie = $request->nfe_serie ?? '';
            $incoming->quantity = str_replace(',', '.', str_replace('.', '', $request->quantity));
            $incoming->value = str_replace(',', '.', str_replace('.', '', $request->value));
            $incoming->save();

            return response()->json([
                'status' => 200,
                'msg' => "Entrada editada com sucesso",
                'incoming' => $incoming
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function deleteIncoming(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do estoque', StockIncoming::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $incoming = StockIncoming::find($request->id);

            if (!$incoming) {
                throw new OperationException('Erro ao ler entrada na operação de alteração de status', StockIncoming::getTableName(), "Entrada não encontrada: {$request->id}", 409);
            }

            // $incoming->quantity = 0;
            // $incoming->value = 0;
            $incoming->status = 0;
            $incoming->save();

            return response()->json([
                'status' => 200,
                'msg' => "Entrada zerada com sucesso",
                'incoming' => $incoming
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    // essa função serve para alinhar todos os cadastros de estoque, vendo todos os lançamentos de produtos de cada lavoura de cada propriedade
    public function alignStocks()
    {
        try {
            checkSection(1);
            ob_implicit_flush(1);  // Ativa o envio imediato da saída

            list($properties, $total) = Property::readProperties(1, null);

            foreach ($properties as $property) {

                echo "########################<br>";
                echo "<b>Propriedade:</b> {$property->name} <br><br>";
                foreach ($property->crops as $crop) {

                    echo "<b>Lavoura:</b> {$crop->crop->name} <br>";
                    echo "<b>Ano agrícola:</b> {$crop->harvest->name} <br><br>";

                    // lançamento de sementes
                    foreach ($crop->data_seed as $data_seed) {
                        echo "<b><u>Produto:</b></u> {$data_seed->product->name}- {$data_seed->product_variant} <br><br>";

                        $stock = Stock::where('product_id', $data_seed->product_id)->where('property_id', $property->id)->where('product_variant', $data_seed->product_variant)->first();

                        if (!$stock) {
                            echo "Produto não encontrado, criando novo estoque... <br>";
                            $stock = new Stock();
                            $stock->product_id = $data_seed->product_id;
                            $stock->property_id = $property->id;
                            $stock->product_variant = $data_seed->product_variant;
                            $stock->save();
                        }

                        // $incoming = $stock->stock_incomings->first();

                        // if (!$incoming) {
                        //     $incoming = new StockIncoming();
                        //     $incoming->stock_id = $stock->id;
                        //     $incoming->value = 0;
                        //     $incoming->quantity = 0;
                        //     $incoming->quantity_unit = 1;
                        //     $incoming->save();
                        // }

                        $exit = new StockExit();
                        $exit->properties_crops_id = $crop->id;
                        $exit->stock_id = $stock->id;
                        $exit->quantity = $data_seed->kilogram_per_ha * $data_seed->area;
                        $exit->type = 'seed';
                        $exit->object_id = $data_seed->id;
                        $exit->save();
                        echo "Dose: {$data_seed->kilogram_per_ha} * {$data_seed->area} = {$exit->quantity}<br>";
                    }

                    // lançamento de fertilizantes e estoques
                    foreach ($crop->data_input as $data_input) {
                        echo "<b><u>Produto:</b></u> {$data_input->product->name} <br><br>";
                        $stock = Stock::where('product_id', $data_input->product_id)->where('property_id', $property->id)->first();

                        if (!$stock) {
                            echo "Produto não encontrado, criando novo estoque... <br>";
                            $stock = new Stock();
                            $stock->product_id = $data_input->product_id;
                            $stock->property_id = $property->id;
                            $stock->product_variant = "";
                            $stock->save();
                        }

                        // $incoming = $stock->stock_incomings->first();

                        // if (!$incoming) {
                        //     $incoming = new StockIncoming();
                        //     $incoming->stock_id = $stock->id;
                        //     $incoming->value = 0;
                        //     $incoming->quantity = 0;
                        //     $incoming->quantity_unit = 2;
                        //     $incoming->save();
                        // }

                        $exit = new StockExit();
                        $exit->properties_crops_id = $crop->id;
                        $exit->stock_id = $stock->id;
                        $exit->quantity = $data_input->dosage * $crop->crop->area;
                        $exit->type = $data_input->type == 1 ? 'fertilizer' : 'defensive';
                        $exit->object_id = $data_input->id;
                        $exit->save();
                        echo "Dose: {$data_input->dosage} * {$crop->crop->area} = {$exit->quantity}<br>";
                    }
                }
                echo "########################<br><br>";
            }
        } catch (OperationException $e) {
            dd([$e->getFile(), $e->getFile(), $e->getMessage()]);
        }
    }
}
