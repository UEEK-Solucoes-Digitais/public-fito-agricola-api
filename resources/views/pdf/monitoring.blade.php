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

        .badge-risk {
            display: block;
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            margin: 0;
            width: fit-content !important
        }

        .badge-risk+.badge-risk {
            margin-top: 3px;
        }

        .badge-risk.green {
            background: #8abb6e;
        }

        .badge-risk.yellow {
            background: #b5ae52;
        }

        .badge-risk.red {
            background: #cc6363;
        }


        img {
            height: 40px;
        }
    </style>
    <title>Relatório geral</title>
</head>

@php
    $colspan = 9;
@endphp

<body>
    <table style="width: 100%" cellspacing="0">
        <thead>
            <tr>
                <th class="no-border" style="text-align: left; padding-bottom: 40px" colspan="{{ $colspan }}">
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
                    <h3 style="margin: 0">Relatório monitoramentos</h3>
                </td>
            </tr>


            @foreach ($reports->groupBy('property_id') as $key => $group_report)
                <tr>
                    <td colspan="{{ $colspan }}" class="no-border">
                        <h2 style="color:#064E43">{{ $group_report->first()->property->name }}</h2>
                    </td>
                </tr>

                @foreach ($group_report->where('property_id', $group_report->first()->property_id)->groupBy('crop_id') as $group_crop)
                    @foreach ($group_crop as $crop)
                        <tr>
                            <td colspan="{{ $colspan }}" class="no-border">
                                <h3 style="color: #8ABB6E">{{ $crop->crop->name }}</h3>
                            </td>
                        </tr>

                        <tr>
                            {{-- <th>Propriedade</th>
                                    <th>Lavoura</th> --}}
                            <th>Cultura</th>
                            <th>Cultivar</th>
                            <th>Ano agrícola</th>
                            <th>Data</th>
                            <th>Estádios</th>
                            <th>Doenças</th>
                            <th>Pragas</th>
                            <th>Daninhas</th>
                            <th>Obs.</th>
                        </tr>
                        {{-- {{ dd($crop->management_data) }} --}}
                        {{-- @foreach ($reports as $object) --}}
                        @foreach ($crop->management_data as $key => $management_data)
                            @php
                                $management_data = (object) $management_data;
                                $stages = '';
                                $diseases = '';
                                $weeds = '';
                                $pests = '';
                                $observations = '';

                                foreach ($management_data->stages as $stage) {
                                    $color = getColor($stage->risk);
                                    $stage_text = "<span class='badge-risk {$color}'>";

                                    if ($stage->vegetative_age_value == 0 && $stage->reprodutive_age_value == 0) {
                                        $stage_text .= 'V0';
                                    } else {
                                        if ($stage->vegetative_age_value > 0) {
                                            $stage_text .= 'V' . str_replace('.0', '', $stage->vegetative_age_value);
                                        }

                                        if ($stage->vegetative_age_value > 0 && $stage->reprodutive_age_value > 0) {
                                            $stage_text .= ' - ';
                                        }

                                        if ($stage->reprodutive_age_value > 0) {
                                            $stage_text .= 'R' . str_replace('.0', '', $stage->reprodutive_age_value);
                                        }
                                    }

                                    $stages .= $stage_text . '</span>';
                                }
                                $stages = substr($stages, 0, -4);

                                foreach ($management_data->diseases as $disease) {
                                    $color = getColor($disease->risk);
                                    $diseases .= "<span class='badge-risk {$color}'>" . $disease->disease->name;

                                    if ($disease->incidency > 0) {
                                        $diseases .= ' - ' . number_format($disease->incidency, 2, ',', '.') . '%';
                                    }

                                    $diseases .= '</span>';
                                }

                                $diseases = substr($diseases, 0, -2);

                                foreach ($management_data->weeds as $weed) {
                                    $color = getColor($weed->risk);
                                    $weeds .= "<span class='badge-risk {$color}'>" . $weed->weed->name . '</span>';
                                }

                                $weeds = substr($weeds, 0, -4);

                                foreach ($management_data->pests as $pest) {
                                    $color = getColor($pest->risk);
                                    $pests .= "<span class='badge-risk {$color}'>" . $pest->pest->name;

                                    if ($pest->incidency > 0) {
                                        $pests .= ' - ' . number_format($pest->incidency, 2, ',', '.') . '%';
                                    }

                                    if ($pest->quantity_per_meter > 0) {
                                        $pests .= ' - ' . number_format($pest->quantity_per_meter, 2, ',', '.') . '/m';
                                    }

                                    if ($pest->quantity_per_square_meter > 0) {
                                        $pests .=
                                            ' - ' .
                                            number_format($pest->quantity_per_square_meter, 2, ',', '.') .
                                            '/m²';
                                    }

                                    $pests .= '</span>';
                                }

                                $pests = substr($pests, 0, -4);

                                $observations = isset($management_data->observations[0])
                                    ? "<span class='badge-risk " .
                                        getColor($management_data->observations[0]->risk) .
                                        "'>" .
                                        getRisk($management_data->observations[0]->risk) .
                                        '</span>'
                                    : '';
                            @endphp
                            <tr>
                                {{-- <td>{{ $crop->property ? $crop->property->name : '--' }}</td>
                                        <td>{{ $crop->crop->name }}</td> --}}
                                <td>{!! $crop->culture_table !!}</td>
                                <td>{!! $crop->culture_code_table !!}</td>
                                <td>{{ $crop->harvest->name }}</td>
                                <td>{{ str_replace('-', '/', $key) }}</td>
                                <td>{!! $stages !!}</td>
                                <td>{!! $diseases !!}</td>
                                <td>{!! $pests !!}</td>
                                <td>{!! $weeds !!}</td>
                                <td>{!! $observations !!}</td>
                            </tr>
                        @endforeach
                        {{-- @endforeach --}}
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>

</html>
