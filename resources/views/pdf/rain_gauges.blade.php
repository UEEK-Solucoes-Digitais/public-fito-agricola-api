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
                    <h3>Relatório pluviômetros</h3>
                </th>
            </tr>
            <tr>
                <th>Propriedade</th>
                <th>Cultura</th>
                <th>Cultivar</th>
                <th>Ano agrícola</th>
                <th>Lavoura</th>
                <th>Total</th>
                <th>Média do volume</th>
                <th>Intervalo sem chuva</th>
                <th>Dias com chuva</th>
                <th>Dias sem chuva</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $object)
                <tr>
                    <td>{{ $object->property ? $object->property->name : '--' }}</td>
                    <td>{!! $object->culture_table !!}</td>
                    <td>{!! $object->culture_code_table !!}</td>
                    <td>{{ $object->harvest->name }}</td>
                    <td>{{ $object->crop->name }}</td>
                    <td>{{ number_format(floatval($object->rain_gauge_infos['total_volume']), 2, ',', '.') . 'mm' }}
                    </td>
                    <td>{{ number_format(floatval($object->rain_gauge_infos['avg_volume']), 2, ',', '.') . 'mm' }}</td>
                    <td>{{ number_format(floatval($object->rain_gauge_infos['rain_interval']), 2, ',', '.') . 'mm' }}
                    </td>
                    <td>{{ number_format(floatval($object->rain_gauge_infos['days_with_rain']), 2, ',', '.') . 'mm' }}
                    </td>
                    <td>{{ number_format(floatval($object->rain_gauge_infos['days_without_rain']), 2, ',', '.') . 'mm' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
