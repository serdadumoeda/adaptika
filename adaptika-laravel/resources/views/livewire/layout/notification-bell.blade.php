<div class="relative" wire:poll.10s="loadNotifications" x-data="{ open: @entangle('isOpen') }">
    <!-- Bell Icon -->
    <button @click="open = !open; if(open) $wire.loadNotifications()" class="relative p-2 text-gray-500 hover:text-gray-700 transition duration-150 ease-in-out focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Unread Badge -->
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 w-80 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg outline-none z-50" style="display: none;">
        <div class="px-4 py-3 flex justify-between items-center bg-gray-50 rounded-t-md">
            <p class="text-sm font-semibold text-gray-700">Notifikasi</p>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800">Tandai semua dibaca</button>
            @endif
        </div>
        
        <div class="py-1 max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="px-4 py-3 hover:bg-gray-50 transition duration-150 cursor-pointer flex justify-between group" wire:click="markAsRead('{{ $notification->id }}')">
                    <div class="flex-1 pr-2">
                        <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? 'Pemberitahuan' }}</p>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $notification->data['message'] ?? '' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center">
                        <span class="h-2 w-2 bg-indigo-600 rounded-full group-hover:bg-indigo-500"></span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    Belum ada notifikasi baru
                </div>
            @endforelse
        </div>
    </div>
</div>
