<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ADAPTIKA') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS (Vercel CDN Fallback) -->
        <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900">
            <div class="mb-6 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-14 h-14 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <span class="text-white text-2xl font-black">A</span>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-wide">ADAPTIKA</h1>
                <p class="text-sm text-indigo-300 mt-1">Adaptive Talent Intelligence & Psychological Analytics</p>
                <p class="text-xs text-slate-500 mt-1">Balai Pelatihan Vokasi dan Produktivitas</p>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white/95 backdrop-blur shadow-2xl overflow-hidden sm:rounded-2xl border border-white/20">
                {{ $slot }}
            </div>

            <p class="text-xs text-slate-600 mt-6">© {{ date('Y') }} Kementerian Ketenagakerjaan RI</p>
        </div>
    </body>
</html>
