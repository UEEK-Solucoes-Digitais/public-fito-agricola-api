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

        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }

        table {
            border-collapse: collapse;
        }

        th,
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
                <th style="text-align: left; border: none; padding: 0" colspan="13">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório pragas</h3>
                </th>
            </tr>
            <tr>
                <th>Propriedade</th>
                <th>Ano agrícola</th>
                <th>Cultura</th>
                <th>Cultivar</th>
                <th>Lavoura</th>
                <th>Data</th>
                <th>Praga</th>
                <th>Nível de risco</th>
                <th>Incidência</th>
                <th>Metro</th>
                <th>m²</th>
                <th>Observações</th>
                <th>Estádio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $object)
                <tr>
                    <td>{{ $object->property_crop->property ? $object->property_crop->property->name : '--' }}</td>
                    <td>{{ $object->property_crop->harvest->name }}</td>
                    <td>{!! $object->property_crop->culture_table !!}</td>
                    <td>{!! $object->property_crop->culture_code_table !!}</td>
                    <td>{{ $object->property_crop->crop->name }}</td>
                    <td>{{ $object->open_date ? date('d/m/Y', strtotime($object->open_date)) : '--' }}
                    </td>
                    <td>{{ $object->pest->name }}</td>
                    <td>{{ getRisk($object->risk) }}</td>
                    <td>{{ $object->incidency . '%' }}</td>
                    <td>{{ number_format($object->quantity_per_meter, 2, ',', '.') }}</td>
                    <td>{{ number_format($object->quantity_per_square_meter, 2, ',', '.') }}</td>
                    <td>{{ $object->pest->observations }}</td>
                    <td>{{ $object->property_crop->stage_table }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
