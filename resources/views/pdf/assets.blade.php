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
                <th style="text-align: left; border: none; padding: 0" colspan="6">
                    <div>
                        <img src="https://api-aws.fitoagricola.com.br//images/logo.jpg" alt='Logo Fito Agrícola'>
                        <p style="float:right; text-align: right">
                            {{ date('d/m/Y H:i') }}<br>
                            Fito Consultoria Agrícola Ltda. Av. Nívio Castelano, 849 - Centro.<br>
                            Lagoa Vermelha - RS.
                        </p>
                    </div>
                    <h3>Relatório Bens</h3>
                </th>
            </tr>
            <tr>
                <th>Nome do bem</th>
                <th>Propriedade</th>
                <th>Tipo</th>
                <th>Valor aproximado</th>
                <th>Observações</th>
                <th>Cadastrado em</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $asset)
                <tr>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->properties_names }}</td>
                    <td>{{ $asset->type }}</td>
                    <td>{{ number_format($asset->value, 2, ',', '.') }}</td>
                    <td>{{ $asset->observations ?? '--' }}</td>
                    <td>{{ date('d/m/Y H:i:s', strtotime($asset->created_at)) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold!important">
                <td colspan="3" style="text-align: right">TOTAL</td>
                <td>{{ number_format($reports->sum('value'), 2, ',', '.') }}</td>
                </td>
                <td colspan="2" style="text-align: right"></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
