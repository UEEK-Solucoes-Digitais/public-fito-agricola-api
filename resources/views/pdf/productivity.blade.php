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
                <th style="text-align: left; border: none; padding: 0" colspan="10">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório produtividade</h3>
                </th>
            </tr>
            <tr>
                <th colspan="7"></th>
                <th colspan="2" style="text-align: center">Produtividade({{ $text_ha }})</th>
                <th colspan="2" style="text-align: center">Produção</th>
            </tr>
            <tr>
                <th>Propriedade</th>
                <th>Ano agrícola</th>
                <th>Cultura</th>
                <th>Lavoura</th>
                <th>Área</th>
                <th>Plantio</th>
                <th>Cultivar</th>
                <th>Sc</th>
                <th>Kg</th>
                <th>Sc</th>
                <th>Kg</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $object)
                <tr>
                    <td>{{ $object->property_crop->property ? $object->property_crop->property->name : '--' }}</td>
                    <td>{{ $object->property_crop->harvest->name }}</td>
                    <td>{!! $object->culture_table !!}</td>
                    <td>{{ $object->property_crop->crop->name }}</td>
                    <td>{{ number_format(floatval($object->data_seed ? $object->data_seed->area : $object->property_crop->crop->area), 2, ',', '.') }}
                        {{ $text_ha }}</td>
                    <td>{{ $object->date_plant }}
                    </td>
                    <td>{!! $object->culture_code_table !!}</td>
                    <td>{{ number_format(floatval($object->productivity_per_hectare), 2, ',', '.') }}</td>
                    <td>{{ number_format(floatval($object->productivity), 2, ',', '.') }}</td>
                    <td>{{ number_format(floatval($object->total_production), 2, ',', '.') }}</td>
                    <td>{{ number_format(floatval($object->total_production_per_hectare), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
