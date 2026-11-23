<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'TradeCore') }}</title>

    <!-- Styles / Scripts -->
    @vite('resources/js/app.ts')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
<div id="app"/>
</body>
</html>
