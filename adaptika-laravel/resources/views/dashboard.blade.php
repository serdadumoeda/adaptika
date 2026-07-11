<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">
            {{ __('Dasbor') }} - {{ auth()->user()->role }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        @if (auth()->user()->role === 'Superadmin')
            @php
                $page = request()->query('page', 'penyelenggara');
            @endphp
            
            @if ($page === 'penyelenggara')
                <livewire:penyelenggara.dashboard-penyelenggara />
            @elseif ($page === 'instruktur')
                <livewire:instruktur.dashboard-instruktur />
            @elseif ($page === 'pengantar')
                <livewire:pengantar.dashboard-pengantar />
            @elseif ($page === 'pemberdayaan')
                <livewire:pemberdayaan.dashboard-pemberdayaan />
            @elseif ($page === 'peserta')
                <livewire:peserta.dashboard-peserta />
            @elseif ($page === 'users')
                <livewire:admin.manajemen-user />
            @elseif ($page === 'kejuruans')
                <livewire:admin.manajemen-kejuruan />
            @elseif ($page === 'settings')
                <livewire:admin.pengaturan-aplikasi />
            @else
                <livewire:penyelenggara.dashboard-penyelenggara />
            @endif
        @elseif (auth()->user()->role === 'Penyelenggara' || auth()->user()->role === 'Kepala Balai')
            <livewire:penyelenggara.dashboard-penyelenggara />
        @elseif (auth()->user()->role === 'Instruktur Teknis')
            <livewire:instruktur.dashboard-instruktur />
        @elseif (auth()->user()->role === 'Pengantar Kerja')
            <livewire:pengantar.dashboard-pengantar />
        @elseif (auth()->user()->role === 'Seksi Pemberdayaan')
            <livewire:pemberdayaan.dashboard-pemberdayaan />
        @else
            <livewire:peserta.dashboard-peserta />
        @endif
    </div>
</x-app-layout>
