<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Adaptika') }}</title>

        <!-- Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Tailwind CSS (Vercel CDN Fallback) -->
        <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
            .sidebar { background-color: #0f172a; } /* Slate 900 */
        </style>
    </head>
    <body class="antialiased text-gray-900 flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="sidebar w-64 h-full flex flex-col text-slate-300 border-r border-slate-800">
            <div class="p-6 flex flex-col items-center border-b border-slate-800">
                <img src="https://kemnaker.go.id/assets/images/logo.png" alt="Kemnaker" class="h-16 w-auto object-contain mb-4">
                <h2 class="text-xl font-bold text-white tracking-wide">ADAPTIKA</h2>
                <p class="text-xs text-slate-400 text-center mt-1">Human-Centric & Psychological Analytics System</p>
            </div>
            
            <div class="p-6 flex-1 flex flex-col space-y-6 overflow-hidden">
                <!-- Navigation -->
                <nav class="flex-1 space-y-4 overflow-y-auto pr-1">
                    @if (auth()->user()->role === 'Superadmin')
                        @php
                            $activePage = request()->query('page', 'penyelenggara');
                        @endphp
                        
                        <div>
                            <p class="text-2xs uppercase tracking-wider text-slate-500 font-bold mb-2 px-3">Analitik & Operasional</p>
                            <div class="space-y-1">
                                <a href="{{ route('dashboard', ['page' => 'penyelenggara']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'penyelenggara' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">🏢</span> Penyelenggara
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'instruktur']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'instruktur' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">🛠️</span> Instruktur Teknis
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'pengantar']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'pengantar' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">🧭</span> Pengantar Kerja
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'pemberdayaan']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'pemberdayaan' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">🤝</span> Pemberdayaan
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'peserta']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'peserta' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">👤</span> Personal Peserta
                                </a>
                            </div>
                        </div>

                        <div>
                            <p class="text-2xs uppercase tracking-wider text-slate-500 font-bold mb-2 px-3">Manajemen Sistem</p>
                            <div class="space-y-1">
                                <a href="{{ route('dashboard', ['page' => 'users']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'users' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">👥</span> Kelola Pengguna
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'kejuruans']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'kejuruans' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">🏫</span> Kelola Kejuruan
                                </a>
                                <a href="{{ route('dashboard', ['page' => 'settings']) }}" class="flex items-center px-3 py-2 text-xs rounded-lg transition-all {{ $activePage === 'settings' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                    <span class="mr-2 text-sm">⚙️</span> Pengaturan Aplikasi
                                </a>
                            </div>
                        </div>
                    @else
                        <div>
                            <p class="text-2xs uppercase tracking-wider text-slate-500 font-bold mb-2 px-3">Menu Utama</p>
                            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 text-xs rounded-lg transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <span class="mr-2 text-sm">📊</span> Dashboard Utama
                            </a>
                        </div>
                    @endif

                    <div>
                        <p class="text-2xs uppercase tracking-wider text-slate-500 font-bold mb-2 px-3">Konfigurasi</p>
                        <a href="{{ route('profile') }}" class="flex items-center px-3 py-2.5 text-xs rounded-lg transition-all {{ request()->routeIs('profile') ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <span class="mr-2 text-sm">⚙️</span> Pengaturan Profil
                        </a>
                    </div>
                </nav>
            </div>

            <div class="p-4 border-t border-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-slate-800 hover:bg-slate-700 hover:text-red-400 text-slate-300 rounded transition-colors border border-slate-700">
                        🚪 Keluar (Logout)
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 h-full overflow-y-auto">
            <!-- Header -->
            @if (isset($header))
                <header class="bg-white shadow-sm sticky top-0 z-10">
                    <div class="max-w-7xl mx-auto py-4 px-6 sm:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <div class="p-6 sm:p-8">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
