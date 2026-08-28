<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Tasks') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <main class="mx-auto max-w-screen-2xl p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
