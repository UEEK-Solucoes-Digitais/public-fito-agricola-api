<h1>Conta criada com sucesso | Sistema Fito Agrícola - {{ date('d/m/Y') }} às {{ date('H:i') }}</h1>
<br>
<p>Olá <b>{{ $admin->name }}</b>, sua conta foi criada com sucesso</p>
<p><b>Usuário:</b> {{ $admin->email }}</p>
<p><b>Senha:</b> {{ $password }}</p>
<br>
<a style="color: #000; font-weight: 600;" href="{{ config('app.system') }}/login">Acessar sistema</a>
<br>
<br>
<h3>
    Fito Agrícola
</h3>
