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

        /* table.no-border,
        table.no-border th,
        table.no-border td {
            border: none;
            border-collapse: collapse;
        }

        table.no-border th,
        table.no-border td {
            padding: 0;
        } */

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
            margin-bottom: 30px;
            border-top: 1px solid black;
        }

        tr.with-border-nested td {
            border-top: 1px solid black;
        }

        tr.sort td {
            background: rgba(138, 187, 110, 0.1);
        }

        table th,
        table td {
            padding: 5px;
        }

        img {
            height: 50px;
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

        .page_break {
            /* page-break-before: always; */
            height: 1px;
            background: #000;
            width: 100%;
            margin: 20px 0;
        }

        .w-background td {
            background: rgba(138, 187, 110, 0.2);
        }
    </style>
    <title>Relatório geral</title>
</head>

@php

    if ($visualization_type != 1 && $visualization_type != 2 && $visualization_type != 3) {
        $visualization_type = 1;
    }

    switch ($visualization_type) {
        case 1:
            $colspan = '8';
            break;
        case 2:
            $colspan = '7';
            break;
        case 3:
            $colspan = '6';
            break;
        default:
            $colspan = '8';
            break;
    }

@endphp

<body>
    <table style="width: 100%" cellspacing="0">
        <thead>
            <tr>
                <th class="no-border" style="text-align: left; padding-bottom: 30px" colspan="{{ $colspan }}">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="{{ $colspan }}" class="no-border">
                    <h3 style="margin-top: 0">Relatório insumos - @switch($visualization_type)
                            @case(1)
                                Data
                            @break

                            @case(2)
                                Produto por Lavoura
                            @break

                            @case(3)
                                Produto por Propriedade
                            @break
                        @endswitch
                    </h3>
                    <hr>
                </td>
            </tr>

            @foreach ($visualization_type != 3 ? $reports->groupBy('property_id') : $reports as $key => $group_report)
                <tr>
                    <td colspan="{{ $colspan }}" class="no-border">
                        <div class="with-margin">
                            <span class="badge-title">
                                {{ $visualization_type == 3 ? $group_report->name : $group_report->first()->property->name }}

                                {{ $visualization_type == 3 ? " - {$group_report->culture_table} - {$group_report->harvest}" : '' }}
                            </span>
                        </div>

                    </td>
                </tr>


                @if ($visualization_type == 3)
                    {{-- Sementes --}}
                    @if ($group_report->merged_data_input->where('type', null)->count() > 0)
                        <tr>
                            <td colspan="{{ $colspan }}" class="no-border ">
                                <h3 style="color:#8ABB6E">Sementes</h3>
                            </td>
                        </tr>
                        <x-GeralTable :currency="$currency" :object="$group_report" visualizationType="{{ $visualization_type }}"
                            colspan="{{ $colspan }}" text="SEMENTE" :mergedInput="$group_report->merged_data_input->where('type', null)" />
                    @endif

                    {{-- Fertilizantes --}}
                    @if ($group_report->merged_data_input->where('type', 1)->count() > 0)
                        <tr>
                            <td colspan="{{ $colspan }}" class="no-border ">
                                <h3 style="color:#8ABB6E">Fertilizantes</h3>
                            </td>
                        </tr>
                        <x-GeralTable :currency="$currency" :object="$group_report" visualizationType="{{ $visualization_type }}"
                            colspan="{{ $colspan }}" text="FERTILIZANTE" :mergedInput="$group_report->merged_data_input->where('type', 1)" type="1" />
                    @endif

                    @for ($i = 1; $i <= 6; $i++)
                        @if ($group_report->merged_data_input->where('type', 2)->where('product.object_type', $i)->count() > 0)
                            <tr>
                                <td colspan="{{ $colspan }}" class="no-border ">
                                    <h3 style="color:#8ABB6E">{{ getObjectType($i) }}</h3>
                                </td>
                            </tr>
                            <x-GeralTable :currency="$currency" :object="$group_report"
                                visualizationType="{{ $visualization_type }}" colspan="{{ $colspan }}"
                                text="{{ mb_strtoupper(getObjectType($i)) }}" :mergedInput="$group_report->merged_data_input
                                    ->where('type', 2)
                                    ->where('product.object_type', $i)" type="2"
                                objectType="{{ $i }}" />
                        @endif
                    @endfor
                @elseif($visualization_type == 1)
                    @foreach ($group_report->groupBy('crop_id') as $group_crop)
                        @foreach ($group_crop as $crop)
                            <tr>
                                <td colspan="{{ $colspan }}" class="no-border">
                                    <h2 style="color: #064E43; margin:0">Lavoura {{ $crop->crop->name }} -
                                        {{ $crop->culture_table }} -
                                        Ano agrícola
                                        {{ $visualization_type == 3 ? $crop->harvest : $crop->harvest->name }}
                                    </h2>
                                </td>
                            </tr>

                            <div style="margin-top: 30px"></div>

                            <tr>
                                <th>Data</th>
                                <th>Nº</th>
                                <th>Classe</th>
                                <th>Produto</th>
                                <th>Dose/ha</th>
                                <th>Quantidade</th>
                                <th>Valor unitário</th>
                                <th>Valor total</th>
                            </tr>

                            @php
                                $total_sum = 0;
                                $total_sum_crop = 0;

                                $total_price = 0;
                                $total_price_crop = 0;

                                $last_group = null;

                                $group = $crop->merged_data_input->groupBy('date')->sortBy(function ($item, $key) {
                                    return $key;
                                });
                            @endphp

                            @foreach ($crop->merged_data_input->sortBy('date')->values() as $merged_data)
                                @php
                                    $border = false;
                                    $application_number =
                                        array_search($merged_data['date'], array_keys($group->toArray())) + 1;

                                    if ($last_group != $application_number) {
                                        $border = true;
                                        $last_group = $application_number;
                                    }

                                    $merged_input = collect($merged_data);

                                    $type =
                                        isset($merged_data['type']) && $merged_data['type'] != null
                                            ? $merged_data['type']
                                            : null;

                                    if ($type != 2) {
                                        $total_sum += $type ? $merged_data['dosage'] : $merged_data['kilogram_per_ha'];
                                        $total_sum_crop += $type
                                            ? $merged_data['dosage']
                                            : $merged_data['kilogram_per_ha'] * $crop->crop->area;
                                    } else {
                                        $total_sum += $merged_data['dosage'];
                                        $total_sum_crop += $merged_data['dosage'] * $crop->crop->area;
                                    }

                                    $total_unit_price = 0;
                                    $total_unit_per_quantity = 0;

                                    if ($visualization_type == 3) {
                                        $total_dosage = $merged_data['total_dosage'];
                                        $total_original_dose = $merged_data['total_dosage'];
                                    } else {
                                        $total_dosage =
                                            (!$type ? $merged_data['kilogram_per_ha'] : $merged_data['dosage']) *
                                            $crop->crop->area;
                                        $total_original_dose = $total_dosage;
                                    }

                                    $avg_price = 0;
                                    $avg_quantity = 0;

                                    if (isset($merged_data['product_variant'])) {
                                        $stock_product =
                                            $visualization_type == 3
                                                ? $crop->stock_incomings
                                                    ->where('stock.product_id', $merged_data['product']['id'])
                                                    ->where('stock.product_variant', $merged_data['product_variant'])
                                                : $crop->property->stock_incomings
                                                    ->where('stock.product_id', $merged_data['product']['id'])
                                                    ->where('stock.product_variant', $merged_data['product_variant']);
                                    } else {
                                        $stock_product =
                                            $visualization_type == 3
                                                ? $crop->stock_incomings->where(
                                                    'stock.product_id',
                                                    $merged_data['product']['id'],
                                                )
                                                : $crop->property->stock_incomings->where(
                                                    'stock.product_id',
                                                    $merged_data['product']['id'],
                                                );
                                    }

                                    $stock_product->each(function ($stock) use (
                                        &$avg_price,
                                        &$total_dosage,
                                        &$avg_quantity,
                                    ) {
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
                                        }
                                    });

                                    $total_unit_price = $avg_quantity > 0 ? $avg_price / $avg_quantity : 0;
                                    $total_unit_per_quantity = $total_unit_price * $total_original_dose;

                                    $total_price += $total_unit_price;
                                    $total_price_crop += $total_unit_per_quantity;
                                @endphp
                                <tr
                                    class="{{ $border ? 'with-border-nested' : '' }} {{ $application_number % 2 == 0 ? 'sort' : '' }}">
                                    <td>{{ date('d/m/Y', strtotime($merged_data['date'])) }}</td>
                                    <td>{{ $application_number }}</td>
                                    <td>
                                        {{ isset($merged_data['type']) && $merged_data['type'] != null
                                            ? ($merged_data['type'] == 1
                                                ? 'Fertilizante'
                                                : getObjectType($merged_data['product']['object_type']))
                                            : 'Semente' }}
                                    </td>
                                    <td>{{ $merged_data['product']['name'] ?? '--' }}</td>
                                    <td>
                                        {{ number_format(!isset($merged_data['type']) || $merged_data['type'] == null ? $merged_data['kilogram_per_ha'] : $merged_data['dosage'], 2, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ number_format((!isset($merged_data['type']) || $merged_data['type'] == null ? $merged_data['kilogram_per_ha'] : $merged_data['dosage']) * $crop->crop->area, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_unit_price, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_unit_per_quantity, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr style="font-weight:bold!important" class="with-border">
                                <td colspan="4">TOTAL</td>

                                <td>{{ number_format($total_sum, 2, ',', '.') }}</td>
                                <td>{{ number_format($total_sum_crop, 2, ',', '.') }}
                                </td>
                                <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_price, 2, ',', '.') }}
                                </td>
                                <td>{{ $currency == 1 ? "R$" : "U$" }}{{ number_format($total_price_crop, 2, ',', '.') }}
                                </td>
                            </tr>
                            <div style="margin-bottom: 30px"></div>
                        @endforeach
                    @endforeach
                @else
                    @foreach ($group_report->groupBy('crop_id') as $group_crop)
                        @foreach ($group_crop as $crop)
                            <tr>
                                <td colspan="{{ $colspan }}" class="no-border">
                                    <h2 style="color: #064E43; margin:0">Lavoura {{ $crop->crop->name }} -
                                        {{ $crop->culture_table }} -
                                        Ano agrícola
                                        {{ $visualization_type == 3 ? $crop->harvest : $crop->harvest->name }}
                                    </h2>
                                </td>
                            </tr>

                            {{-- Sementes --}}

                            @if ($crop->merged_data_input->where('type', null)->count() > 0)
                                <tr>
                                    <td colspan="{{ $colspan }}" class="no-border ">
                                        <h3 style="color:#8ABB6E">Sementes</h3>
                                    </td>
                                </tr>
                                <x-GeralTable :currency="$currency" :object="$crop"
                                    visualizationType="{{ $visualization_type }}" colspan="{{ $colspan }}"
                                    :mergedInput="$crop->merged_data_input->where('type', null)" text="SEMENTE" />
                            @endif

                            {{-- FERTILIZANTE --}}
                            @if ($crop->merged_data_input->where('type', 1)->count() > 0)
                                <td colspan="{{ $colspan }}" class="no-border ">
                                    <tr>
                                        <h3 style="color:#8ABB6E">Fertilizantes</h3>
                                </td>
                                </tr>
                                <x-GeralTable :currency="$currency" :object="$crop"
                                    visualizationType="{{ $visualization_type }}" colspan="{{ $colspan }}"
                                    text="FERTILIZANTE" :mergedInput="$crop->merged_data_input->where('type', 1)" type="1" />
                            @endif

                            @for ($i = 1; $i <= 6; $i++)
                                @if ($crop->merged_data_input->where('type', 2)->where('product.object_type', $i)->count() > 0)
                                    <tr>
                                        <td colspan="{{ $colspan }}" class="no-border ">
                                            <h3 style="color:#8ABB6E">{{ getObjectType($i) }}</h3>
                                        </td>
                                    </tr>
                                    <x-GeralTable :currency="$currency" :object="$crop"
                                        visualizationType="{{ $visualization_type }}" colspan="{{ $colspan }}"
                                        text="{{ mb_strtoupper(getObjectType($i)) }}" :mergedInput="$crop->merged_data_input
                                            ->where('type', 2)
                                            ->where('product.object_type', $i)" type="2"
                                        objectType="{{ $i }}" />
                                @endif
                            @endfor
                        @endforeach
                    @endforeach
                @endif
            @endforeach



            {{-- @for ($i = 1; $i <= 6; $i++)
                @if ($options["has_defensive_$i"])
                    <tr>
                        <td colspan="{{ $colspan }}" class="no-border">
                            <h3>{{ getObjectType($i) }}</h3>
                        </td>
                    </tr>
                    <tr>
                        <th>Propriedade</th>
                        @if ($visualization_type != 3)
                            <th>Lavoura</th>
                        @endif
                        <th>Cultura</th>
                        <th>Ano agrícola</th>
                        @if ($visualization_type == 1)
                            <th>Data</th>
                        @endif
                        <th>Produto</th>
                        <th>Dose/ha</th>
                        <th>Quantidade</th>
                    </tr>

                    @php
                        $total_sum = 0;
                        $total_sum_crop = 0;
                    @endphp
                    @foreach ($reports as $object)
                        @php
                            $merged_input = collect($object['merged_data_input']);
                            $total_sum += $merged_input->where('type', '!=', 1)->where('product.object_type', $i)->sum('dosage');
                            $total_sum_crop += $merged_input->where('type', '!=', 1)->where('product.object_type', $i)->sum('dosage') * ($visualization_type == 3 ? $object['crop_area'] : $object['crop']['area']);
                        @endphp
                        @foreach ($merged_input->where('type', '!=', 1)->where('product.object_type', $i) as $merged_data_input)
                            <tr>
                                <td>{{ $visualization_type == 3 ? $object['name'] : $object['property']['name'] }}</td>
                                @if ($visualization_type != 3)
                                    <td>{{ $object['crop']['name'] }}</td>
                                @endif
                                <td>{!! $object['culture_table'] !!}</td>
                                <td>{{ $visualization_type == 3 ? $object['harvest'] : $object['harvest']['name'] }}
                                </td>
                                @if ($visualization_type == 1)
                                    <td>{{ date('d/m/Y', strtotime($merged_data_input['date'])) }}</td>
                                @endif
                                </td>
                                <td>{{ $merged_data_input['product'] ? $merged_data_input['product']['name'] : '--' }}
                                </td>
                                <td>{{ number_format(isset($merged_data_input['type']) ? $merged_data_input['dosage'] : $merged_data_input['kilogram_per_ha'], 2, ',', '.') }}
                                </td>
                                <td>{{ number_format((isset($merged_data_input['type']) ? $merged_data_input['dosage'] : $merged_data_input['kilogram_per_ha']) * ($visualization_type == 3 ? $object['crop_area'] : $object['crop']['area']), 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr style="background: #8abb6e; font-weight:bold!important">
                        <td colspan="{{ $colspan - 3 }}"></td>
                        <td>TOTAL {{ mb_strtoupper(getObjectType($i)) }}</td>

                        <td>{{ number_format($total_sum, 2, ',', '.') }}
                        </td>
                        <td>{{ number_format($total_sum_crop, 2, ',', '.') }}
                        </td>
                    </tr>
                @endif
            @endfor --}}
        </tbody>
    </table>

</body>

</html>
