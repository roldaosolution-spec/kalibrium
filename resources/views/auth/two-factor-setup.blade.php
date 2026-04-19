<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Configuração de 2FA Obrigatória</title>
</head>
<body>
    <h1>Autenticação de Dois Fatores Obrigatória</h1>
    <p>
        Seu perfil ({{ auth()->user()?->role?->label() }}) exige autenticação de dois fatores (2FA)
        antes de acessar o sistema.
    </p>
    <p>Configure o 2FA agora para continuar:</p>
    <form method="POST" action="/user/two-factor-authentication" id="enable-2fa-form">
        @csrf
    </form>
    <button type="submit" form="enable-2fa-form">Configurar 2FA</button>
    <form method="POST" action="/logout">
        @csrf
        <button type="submit">Sair</button>
    </form>
</body>
</html>
