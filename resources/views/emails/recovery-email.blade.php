<h1>Recuperação de senha | Sistema Fito Agrícola - {{ date('d/m/Y') }} às {{ date('H:i') }}</h1>
<br>
<p>Olá <b>{{ $admin->name }}</b>, foi solicitada uma recuperação de senha em seu Email</p>
<p>Caso não tenha sido você, desconsidere esse email. Caso contrário, clique no botão abaixo para prosseguir</p>
<br>
<a style="color: #000; font-weight: 600;"
    href="{{ config('app.system') }}/recuperar-senha/{{ base64_encode($hash) }}">Prosseguir com recuperação</a>
<br>
<br>
<h3>
    Fito Agrícola
</h3>
