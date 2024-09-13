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
            border: 1px solid black;
            text-align: left;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 5px;
        }

        img {
            height: 40px;
        }
    </style>
    <title>Relatório geral</title>
</head>

<body>

    <table style="width: 100%" cellspacing="0">
        <thead>
            <tr>
                <th style="text-align: left; border: none; padding: 0" colspan="8">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório geral</h3>
                </th>
            </tr>

        </thead>
        <tbody>
            @foreach ($reports->groupBy('property_id') as $group_property)
                <tr>
                    <td colspan="8" class="no-border">
                        <h2 style="color: #064E43">
                            {{ $group_property->first()->property ? $group_property->first()->property->name : '--' }}
                        </h2>
                    </td>
                </tr>

                <tr>
                    {{-- <th>Propriedade</th> --}}
                    <th>Lavoura</th>
                    <th>Cultura</th>
                    <th>Cultivar</th>
                    <th>Área</th>
                    <th>DAP*</th>
                    <th>DAE*</th>
                    <th>DAA*</th>
                    <th>Estádio</th>
                </tr>

                @foreach ($group_property as $object)
                    <tr>
                        {{-- <td>{{ $object->property ? $object->property->name : '--' }}</td> --}}
                        <td>{{ $object->crop->name }}</td>
                        <td>{!! $object->culture_table !!}</td>
                        <td>{!! $object->culture_code_table !!}</td>
                        <td>{{ number_format($object->crop->area, 2, ',', '.') . 'ha' }}</td>
                        <td>{{ $object->plant_table }}</td>
                        <td>{{ $object->emergency_table }}</td>
                        <td>{{ $object->application_table }}</td>
                        <td>{{ $object->stage_table }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>

</html>
