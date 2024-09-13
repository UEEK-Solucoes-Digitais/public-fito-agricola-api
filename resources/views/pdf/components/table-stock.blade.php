<tr>
    @foreach ($trs as $tr)
        <th>{{ $tr }}</th>
    @endforeach
</tr>

@php
    $sum = 0;
    $sum_quantity = 0;
    $sum_quantity_price = 0;
@endphp

@foreach ($groups as $group)
    @php

        if ($tab == 1) {
            $sum += $group->stock_quantity_number;
        } elseif ($tab == 2) {
            $sum_quantity += $group->quantity;
            $sum_quantity_price += $group->value * $group->quantity;
            $sum += $group->value;
        } elseif ($tab == 3) {
            $sum += $group->quantity;
        }
    @endphp
    <tr>
        @switch($tab)
            @case(1)
                <td>{{ $group->product->name . ' ' . $group->product_variant ?? '' }}</td>
                <td>{{ $group->stock_quantity }}</td>
                <td>{{ $group->created_at }}</td>
            @break

            @case(2)
                <td>{{ $group->stock->product->name }}</td>
                <td>{{ number_format($group->quantity, 2, ',', '.') }}</td>
                <td>{{ number_format($group->value * $group->quantity, 2, ',', '.') }}</td>
                <td>{{ number_format($group->value, 2, ',', '.') }}</td>
                <td>{{ $group->supplier_name ?? '--' }}</td>
                <td>{{ $group->entry_date ?? '--' }}</td>
                <td>{{ $group->nfe_number ?? '--' }}{{ $group->nfe_serie ? "- {$group->nfe_serie}" : '' }}</td>
            @break

            @case(3)
                <td>{{ $group->stock->product->name }}</td>
                <td>{{ number_format($group->quantity, 2, ',', '.') }}</td>
                <td>{{ $group->created_at }}</td>
            @break
        @endswitch
    </tr>
@endforeach

<tr style="font-weight:bold!important" class="with-border">
    @switch($tab)
        @case(1)
            <td>Total</td>
            <td colspan="2">{{ gettype($sum_quantity) == 'string' ? $sum : number_format($sum, 2, ',', '.') }}</td>
        @break

        @case(2)
            <td>Total</td>
            <td colspan="1">
                {{ gettype($sum_quantity) == 'string' ? $sum_quantity : number_format($sum_quantity, 2, ',', '.') }}
            </td>
            <td colspan="1">
                {{ gettype($sum_quantity_price) == 'string' ? $sum_quantity_price : number_format($sum_quantity_price, 2, ',', '.') }}
            </td>
            <td colspan="4">{{ gettype($sum) == 'string' ? $sum : number_format($sum, 2, ',', '.') }}</td>
        @break

        @case(3)
            <td>Total</td>
            <td colspan="2">{{ gettype($sum_quantity) == 'string' ? $sum : number_format($sum, 2, ',', '.') }}</td>
        @break
    @endswitch
</tr>
