<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Verificação em Duas Etapas</title>
</head>
<body>
    <form method="POST" action="/two-factor-challenge">
        @csrf
        <div>
            <label for="code">Código TOTP</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code">
        </div>
        <div>
            <label for="recovery_code">Código de Recuperação</label>
            <input id="recovery_code" type="text" name="recovery_code" autocomplete="one-time-code">
        </div>
        @if ($errors->any())
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <button type="submit">Verificar</button>
    </form>
</body>
</html>
