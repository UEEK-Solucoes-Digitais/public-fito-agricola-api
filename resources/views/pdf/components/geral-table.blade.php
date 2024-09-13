<tr>
    <th>Propriedade</th>
    @if ($visualizationType != 3)
        <th>Lavoura</th>
    @endif
    @if ($visualizationType == 1)
        <th>Data</th>
    @endif
    <th>Produto</th>
    <th>Dose/ha</th>
    <th>Quantidade</th>
    <th>Valor unitário</th>
    <th>Valor total</th>
</tr>
@php
    $total_sum = 0;
    $total_sum_crop = 0;

    $merged_input = collect($mergedInput);

    if ($type != 2) {
        $total_sum += $merged_input->sum($type ? 'dosage' : 'kilogram_per_ha');
        $total_sum_crop +=
            $visualizationType == 3
                ? $merged_input->sum('total_dosage')
                : $merged_input->sum($type ? 'dosage' : 'kilogram_per_ha') * $object['crop']['area'];
    } else {
        $total_sum += $merged_input->where('type', $type)->where('product.object_type', $objectType)->sum('dosage');
        $total_sum_crop +=
            $visualizationType == 3
                ? $merged_input->where('type', $type)->where('product.object_type', $objectType)->sum('total_dosage')
                : $merged_input->where('type', $type)->where('product.object_type', $objectType)->sum('dosage') *
                    $object['crop']['area'];
    }

    $total_price = 0;
    $total_price_crop = 0;

    // dd($object->stock_exits->sum('quantity'));

@endphp
@foreach ($merged_input as $merged_data_input)
    @php
        if ($visualizationType == 3) {
            $total_dosage = $merged_data_input['total_dosage'];
            $total_original_dose = $merged_data_input['total_dosage'];
        } else {
            $total_dosage =
                (!$type ? $merged_data_input['kilogram_per_ha'] : $merged_data_input['dosage']) *
                $object['crop']['area'];
            $total_original_dose = $total_dosage;
        }

        $avg_price = 0;
        $avg_quantity = 0;

        if (isset($merged_data_input['product_variant'])) {
            $stock_product =
                $visualizationType == 3
                    ? $object->stock_incomings
                        ->where('stock.product_id', $merged_data_input['product']['id'])
                        ->where('stock.product_variant', $merged_data_input['product_variant'])
                    : $object->property->stock_incomings
                        ->where('stock.product_id', $merged_data_input['product']['id'])
                        ->where('stock.product_variant', $merged_data_input['product_variant']);
        } else {
            $stock_product =
                $visualizationType == 3
                    ? $object->stock_incomings->where('stock.product_id', $merged_data_input['product']['id'])
                    : $object->property->stock_incomings->where(
                        'stock.product_id',
                        $merged_data_input['product']['id'],
                    );
        }

        $stock_product->each(function ($stock) use (&$avg_price, &$total_dosage, &$avg_quantity) {
            if ($total_dosage > 0) {
                if ($stock->quantity > $total_dosage) {
                    $total_to_use = $total_dosage;
                } else {
                    $total_to_use = $stock->quantity - $total_dosage;

                    if ($total_to_use < 1) {
                        $total_to_use = $stock->quantity;
                    }
                }

                $avg_price += $stock->value * $total_to_use;
                $avg_quantity += $total_to_use;

                $total_dosage -= $total_to_use;
                // echo "Quantity: $stock->quantity <br> Value: $stock->value <br> Total Dosage: $total_dosage <br> Total to use: $total_to_use <br><br>";
            }
        });

        $total_unit_price = $avg_quantity > 0 ? $avg_price / $avg_quantity : 0;
        $total_unit_per_quantity = $total_unit_price * $total_original_dose;

        $total_price += $total_unit_price;
        $total_price_crop += $total_unit_per_quantity;
    @endphp
    <tr>
        <td>{{ $visualizationType == 3 ? $object['name'] : $object['property']['name'] }}</td>
        @if ($visualizationType != 3)
            <td>{{ $object['crop']['name'] }}</td>
        @endif
        @if ($visualizationType == 1)
            <td>{{ date('d/m/Y', strtotime($merged_data_input['date'])) }}</td>
        @endif
        <td>{{ $merged_data_input['product']['name'] ?? '--' }}
        </td>
        <td>{{ number_format(!$type ? $merged_data_input['kilogram_per_ha'] : $merged_data_input['dosage'], 2, ',', '.') }}
        </td>
        <td>{{ number_format($visualizationType == 3 ? $merged_data_input['total_dosage'] : (!$type ? $merged_data_input['kilogram_per_ha'] : $merged_data_input['dosage']) * $object['crop']['area'], 2, ',', '.') }}
        </td>
        <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_unit_price, 2, ',', '.') }}</td>
        <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_unit_per_quantity, 2, ',', '.') }}</td>
    </tr>
@endforeach

<tr style="font-weight:bold!important" class="with-border">
    <td colspan="{{ $colspan - 5 }}"></td>
    <td>TOTAL {{ $text }}</td>

    <td>{{ number_format($total_sum, 2, ',', '.') }}</td>
    <td>{{ number_format($total_sum_crop, 2, ',', '.') }}
    </td>
    <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_price, 2, ',', '.') }}</td>
    <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_price_crop, 2, ',', '.') }}</td>
</tr>
<br>
