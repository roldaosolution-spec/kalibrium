<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Início</title>
</head>
<body>
    <p>Bem-vindo, {{ auth()->user()->name }}.</p>
    <form method="POST" action="/logout">
        @csrf
        <button type="submit">Sair</button>
    </form>
</body>
</html>
