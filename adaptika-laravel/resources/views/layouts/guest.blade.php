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
                <div class="flex items-center justify-center mb-3">
                    <img
                        src="{{ asset('logo-adaptika.png') }}"
                        alt="Logo ADAPTIKA"
                        class="w-28 h-28 object-contain drop-shadow-[0_0_18px_rgba(99,102,241,0.6)]"
                    >
                </div>
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
