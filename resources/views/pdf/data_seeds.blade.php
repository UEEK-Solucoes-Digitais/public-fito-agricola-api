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
                <th style="text-align: left; border: none; padding: 0" colspan="9">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório sementes</h3>
                </th>
            </tr>
            <tr>
                <th>Propriedade</th>
                <th>Plantio</th>
                <th>Ano agrícola</th>
                <th>Cultura</th>
                <th>Cultivar</th>
                <th>Lavoura</th>
                <th>Semente Kg/ha</th>
                <th>População/ha</th>
                <th>% de emergência</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $object)
                @foreach ($object->data_population as $data_population)
                    <tr>
                        <td>{{ $object->property_crop->property ? $object->property_crop->property->name : '--' }}</td>
                        <td>{{ date('d/m/Y', strtotime($object->date)) }}</td>
                        <td>{{ $object->property_crop->harvest->name }}</td>
                        <td>{{ $object->product->name }}</td>
                        <td>{{ $object->product_variant }}</td>
                        <td>{{ $object->property_crop->crop->name }}</td>
                        <td>{{ number_format($data_population->plants_per_hectare, 2, ',', '.') }}</td>
                        <td>{{ number_format($data_population->quantity_per_ha, 2, ',', '.') }}</td>
                        <td>{{ number_format($data_population->emergency_percentage, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>

</html>
