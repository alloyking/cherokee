<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">

        <title>{{ $title ?? config('app.name', 'Cherokee') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <header class="sticky top-0 z-50 border-b border-white/10 bg-black/40 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 lg:px-6">
                <a href="{{ url('/') }}" class="inline-block focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2 focus:ring-offset-zinc-950 rounded" aria-label="deceptio.ai – Home">
                    <img src="{{ asset('images/logo-header.png') }}" alt="" class="h-10 w-auto sm:h-12" width="240" height="48">
                </a>

                <nav class="flex items-center gap-6">
                    @auth
                        <a href="{{ route('words.index') }}" class="text-sm font-medium text-zinc-300 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2 focus:ring-offset-zinc-950 rounded px-1">
                            words
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-zinc-300 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2 focus:ring-offset-zinc-950 rounded px-1">
                                Log out
                            </button>
                        </form>
                    @endauth
                </nav>
            </div>
        </header>

        {{ $slot }}

        @livewireScripts
    </body>
</html>
