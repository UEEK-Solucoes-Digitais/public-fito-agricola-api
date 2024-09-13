<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Fito Agrícola API</title>

    <style>
        body {
            margin: 0px;
            padding: 0px;
        }

        main {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100vh;
            background: #EFEFEF;
        }

        main img {
            width: 550px;
        }
    </style>
</head>

<body>
    <main>
        <img src="{{ url('images/logo.svg') }}" alt="Fito Agrícola">
    </main>
</body>

</html>
