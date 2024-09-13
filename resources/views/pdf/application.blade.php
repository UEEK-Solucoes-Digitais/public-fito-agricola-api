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
                <th style="text-align: left; border: none; padding: 0" colspan="14">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório fungicidas</h3>
                </th>
            </tr>
            <tr>
                <th>Propriedade</th>
                <th>Plantio</th>
                <th>Ano agrícola</th>
                <th>Cultura</th>
                <th>Cultivar</th>
                <th>Lavoura</th>
                <th>N•</th>
                <th>Data</th>
                <th>DEPUA - Fungicida</th>
                <th>DEPPA - Fungicida</th>
                <th>DAA - Fungicida</th>
                <th>DAE</th>
                <th>DAP</th>
                <th>Estádio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $object)
                <tr>
                    <td>{{ $object->property ? $object->property->name : '--' }}</td>
                    <td>{{ $object->date_plant }}</td>
                    <td>{{ $object->harvest->name }}</td>
                    <td>{!! $object->culture_table !!}</td>
                    <td>{!! $object->culture_code_table !!}</td>
                    <td>{{ $object->crop->name }}</td>
                    <td>{{ $object->application_number }}</td>
                    <td>{{ $object->application_date_table }}</td>
                    <td>{{ $object->days_between_plant_and_last_application }}</td>
                    <td>{{ $object->days_between_plant_and_first_application }}</td>
                    <td>{{ $object->application_table }}</td>
                    <td>{{ $object->emergency_table }}</td>
                    <td>{{ $object->plant_table }}</td>
                    <td>{{ $object->stage_table }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
