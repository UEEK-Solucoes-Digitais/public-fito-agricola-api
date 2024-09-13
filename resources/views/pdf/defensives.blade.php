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

        th {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            border-collapse: collapse;
        }

        tr.with-border td {
            border-top: 1px solid black;
        }

        tbody tr:last-child td {
            border-top: 1px solid black;
        }

        table {
            border-collapse: collapse;
        }

        th {
            text-align: left;
        }

        tr.sort td {
            background: rgba(138, 187, 110, 0.1);
        }

        th {
            padding: 10px;
        }

        td {
            padding: 3px 5px;
        }

        img {
            height: 40px;
        }
    </style>
    <title>Relatório geral</title>
</head>

<body>

    <table style="width: 100%">
        <thead>
            <tr>
                <th style="text-align: left; border: none; padding: 0" colspan="6">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>
                        Relatório Defensivos {{ $reports->property ? " - {$reports->property->name}" : '--' }}
                        <br>Lavoura {{ $reports->crop ? $reports->crop->name : '--' }}
                        <br>Ano agrícola {{ $reports->harvest ? $reports->harvest->name : '--' }}
                    </h3>
                </th>
            </tr>
            <tr>
                {{-- <th>Propriedade</th> --}}
                {{-- <th>Ano agrícola</th> --}}
                {{-- <th>Lavoura</th> --}}
                <th>Data</th>
                <th>N•</th>
                <th>Tipo de insumo</th>
                <th>Produto</th>
                <th>Dose</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $last_group = null;
                $group = $reports->data_input
                    ->where('type', 2)
                    ->groupBy('date')
                    ->sortBy(function ($item, $key) {
                        return $key;
                    });
            @endphp
            @foreach ($reports->data_input->sortBy('date')->where('type', 2) as $object)
                @php
                    $border = false;
                    $application_number = array_search($object->date, array_keys($group->toArray())) + 1;

                    if ($last_group != $application_number) {
                        $border = true;
                        $last_group = $application_number;
                    }
                @endphp
                <tr class="{{ $border ? 'with-border' : '' }} {{ $application_number % 2 == 0 ? 'sort' : '' }}">
                    {{-- <td>{{ $reports->property ? $reports->property->name : '--' }}</td> --}}
                    {{-- <td>{{ $reports->harvest->name }}</td> --}}
                    {{-- <td>{{ $reports->crop->name }}</td> --}}
                    <td>{{ $object->date ? date('d/m/Y', strtotime($object->date)) : '--' }}
                    </td>
                    <td>{{ array_search($object->date, array_keys($group->toArray())) + 1 }}</td>
                    <td>{{ $object->product ? getObjectType($object->product->object_type) : 'produto' }}</td>
                    <td>{{ $object->product ? $object->product->name : '--' }}</td>
                    <td>{{ number_format($object->dosage, 2, ',', '.') }}</td>
                    <td>{{ number_format($object->dosage * $reports->crop->area, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold!important">
                <td colspan="4" style="text-align: right">TOTAL</td>
                <td>{{ number_format($reports->data_input->where('type', 2)->sum('dosage'), 2, ',', '.') }}</td>
                <td>{{ number_format($reports->data_input->where('type', 2)->sum('dosage') * $reports->crop->area, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
