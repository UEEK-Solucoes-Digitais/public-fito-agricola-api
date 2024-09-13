<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body {
            font-size: 14px
        }

        .no-border {
            border: none !important;
            padding: 0;
        }

        table {
            border: none
        }

        table th,
        table td {
            text-align: left;
            border-collapse: collapse;
        }

        table th {
            border-bottom: 1px solid black;
        }

        table tr.with-border td {
            border-top: 1px solid black;
        }

        table th,
        table td {
            padding: 5px;
        }

        img {
            height: 40px;
        }

        .badge-title {
            border-radius: 10px;
            color: #fff;
            background: #064E43;
            padding: 8px 10px;
            width: fit-content !important;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold
        }

        .with-margin {
            margin: 20px 0;
        }
    </style>
    <title>Relatório Estoques</title>
</head>

<body>

    @php
        $productTypes = [
            1 => 'Sementes',
            2 => 'Defensivos',
            3 => 'Fertilizantes',
        ];

        switch ($tab) {
            case 1:
                $trs = ['Nome do item', 'Quantidade em estoque', 'Data de Criação'];
                break;
            case 2:
                $trs = ['Produto', 'Quantidade', 'Valor', 'Valor Unitário', 'Fornecedor', 'Data NF', 'NF-e'];
                break;
            case 3:
                $trs = ['Produto', 'Quantidade utilizada', 'Data de Criação'];
                break;
        }
    @endphp

    <table style="width: 100%" cellspacing="0">
        <thead>
            <tr>
                <th style="text-align: left; border: none; padding: 0" colspan="{{ count($trs) }}">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório Estoques</h3>
                </th>
            </tr>

        </thead>
        <tbody>
            @foreach ($reports->groupBy($tab == 1 ? 'property_id' : 'stock.property_id') as $key => $group_report)
                @if ($tab == 3)
                    <td colspan="{{ count($trs) }}" class="no-border">
                        <div class="with-margin">
                            <span class="badge-title">
                                {{ $group_report->first()->crop_join->property ? $group_report->first()->crop_join->property->name : '--' }}
                            </span>
                        </div>

                    </td>
                    @foreach ($group_report->groupBy('crop_join.crop_id') as $crop_group)
                        <tr>
                            <td colspan="{{ count($trs) }}" class="no-border">
                                <h2 style="color:#064E43">{{ $crop_group->first()->crop_join->crop->name }}</h2>
                            </td>
                        </tr>
                        @if ($crop_group->where($tab == 1 ? 'product.type' : 'stock.product.type', 1)->count() > 0)
                            <tr>
                                <td colspan="{{ count($trs) }}" class="no-border ">
                                    <h3 style="color:#8ABB6E">Sementes</h3>
                                </td>
                            </tr>

                            <x-StockTable :groups="$crop_group->where($tab == 1 ? 'product.type' : 'stock.product.type', 1)" :trs="$trs" colspan="{{ count($trs) }}"
                                :tab="$tab" />
                        @endif
                        @if ($crop_group->where($tab == 1 ? 'product.type' : 'stock.product.type', 3)->count() > 0)
                            <tr>
                                <td colspan="{{ count($trs) }}" class="no-border ">
                                    <h3 style="color:#8ABB6E">Fertilizantes</h3>
                                </td>
                            </tr>

                            <x-StockTable :groups="$crop_group->where($tab == 1 ? 'product.type' : 'stock.product.type', 3)" :trs="$trs" colspan="{{ count($trs) }}"
                                :tab="$tab" />
                        @endif
                        @for ($i = 1; $i <= 6; $i++)
                            @if ($crop_group->where($tab == 1 ? 'product.type' : 'stock.product.type', 2)->where($tab == 1 ? 'product.object_type' : 'stock.product.object_type', $i)->count() > 0)
                                <tr>
                                    <td colspan="{{ count($trs) }}" class="no-border ">
                                        <h3 style="color:#8ABB6E">{{ getObjectType($i) }}</h3>
                                    </td>
                                </tr>

                                <x-StockTable :groups="$crop_group
                                    ->where($tab == 1 ? 'product.type' : 'stock.product.type', 2)
                                    ->where($tab == 1 ? 'product.object_type' : 'stock.product.object_type', $i)" :trs="$trs" colspan="{{ count($trs) }}"
                                    :tab="$tab" />
                            @endif
                        @endfor
                    @endforeach
                @else
                    <td colspan="{{ count($trs) }}" class="no-border">
                        <div class="with-margin">
                            <span class="badge-title">
                                {{ $group_report->first()->property ? $group_report->first()->property->name : '--' }}
                            </span>
                        </div>

                    </td>
                    @if ($group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 1)->count() > 0)
                        <tr>
                            <td colspan="{{ count($trs) }}" class="no-border ">
                                <h3 style="color:#8ABB6E">Sementes</h3>
                            </td>
                        </tr>

                        <x-StockTable :groups="$group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 1)" :trs="$trs" colspan="{{ count($trs) }}"
                            :tab="$tab" />
                    @endif
                    @if ($group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 3)->count() > 0)
                        <tr>
                            <td colspan="{{ count($trs) }}" class="no-border ">
                                <h3 style="color:#8ABB6E">Fertilizantes</h3>
                            </td>
                        </tr>

                        <x-StockTable :groups="$group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 3)" :trs="$trs" colspan="{{ count($trs) }}"
                            :tab="$tab" />
                    @endif
                    @for ($i = 1; $i <= 6; $i++)
                        @if ($group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 2)->where($tab == 1 ? 'product.object_type' : 'stock.product.object_type', $i)->count() > 0)
                            <tr>
                                <td colspan="{{ count($trs) }}" class="no-border ">
                                    <h3 style="color:#8ABB6E">{{ getObjectType($i) }}</h3>
                                </td>
                            </tr>

                            <x-StockTable :groups="$group_report
                                ->where($tab == 1 ? 'product.type' : 'stock.product.type', 2)
                                ->where($tab == 1 ? 'product.object_type' : 'stock.product.object_type', $i)" :trs="$trs" colspan="{{ count($trs) }}"
                                :tab="$tab" />
                        @endif
                    @endfor
                    @if ($group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 4)->count() > 0)
                        <tr>
                            <td colspan="{{ count($trs) }}" class="no-border ">
                                <h3 style="color:#8ABB6E">Outros</h3>
                            </td>
                        </tr>

                        <x-StockTable :groups="$group_report->where($tab == 1 ? 'product.type' : 'stock.product.type', 4)" :trs="$trs" colspan="{{ count($trs) }}"
                            :tab="$tab" />
                    @endif
                @endif
            @endforeach
            {{-- @foreach ($reports as $stock)
                <tr>
                    @switch($tab)
                        @case(1)
                            <td>{{ $stock->id }}</td>
                            <td>{{ $stock->product->name . ' ' . $stock->product_variant ?? '' }}</td>
                            <td>{{ $productTypes[$stock->product->type] }}</td>
                            <td>{{ $stock->property->name }}</td>
                            <td>{{ $stock->stock_quantity }}</td>
                            <td>{{ $stock->created_at }}</td>
                        @break

                        @case(2)
                            <td>{{ $stock->id }}</td>
                            <td>{{ $stock->stock->product->name }}</td>
                            <td>{{ $productTypes[$stock->stock->product->type] }}</td>
                            <td>{{ $stock->stock->property->name }}</td>
                            <td>{{ number_format($stock->quantity, 2, ',', '.') }}</td>
                            <td>{{ number_format($stock->value * $stock->quantity, 2, ',', '.') }}</td>
                            <td>{{ number_format($stock->value, 2, ',', '.') }}</td>
                            <td>{{ $stock->created_at }}</td>
                        @break

                        @case(3)
                            <td>{{ $stock->id }}</td>
                            <td>{{ $stock->stock->product->name }}</td>
                            <td>{{ $productTypes[$stock->stock->product->type] }}</td>
                            <td>{{ number_format($stock->quantity, 2, ',', '.') }}</td>
                            <td>{{ $stock->stock->property->name }}</td>
                            <td>{{ $stock->crop_join->crop->name }}</td>
                            <td>{{ $stock->created_at }}</td>
                        @break
                    @endswitch
                </tr>
            @endforeach
            <tr>
                @switch($tab)
                    @case(1)
                        <td colspan='3'></td>
                        <td>Total</td>
                        <td>{{ number_format($reports->sum('stock_quantity_number'), 2, ',', '.') }}</td>
                        <td></td>,
                    @break

                    @case(2)
                        <td colspan='3'></td>
                        <td>Total</td>
                        <td>{{ number_format($reports->sum('quantity'), 2, ',', '.') }}</td>
                        <td>{{ number_format($reports->sum('value') * $reports->sum('quantity'), 2, ',', '.') }}
                        </td>
                        <td>{{ number_format($reports->sum('value'), 2, ',', '.') }}</td>
                        <td></td>,
                    @break

                    @case(3)
                        <td colspan='2'></td>
                        <td>Total</td>
                        <td>{{ number_format($reports->sum('quantity'), 2, ',', '.') }}</td>
                        <td colspan='3'></td>
                    @break
                @endswitch
            </tr> --}}
        </tbody>
    </table>
</body>

</html>
