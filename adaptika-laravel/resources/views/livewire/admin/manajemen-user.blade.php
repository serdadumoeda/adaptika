<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold">👥 Manajemen Pengguna Sistem</h3>
            <p class="text-gray-600">Kelola akun operator BPVP beserta peran dan akses kejuruannya.</p>
        </div>
        <button wire:click="openCreateForm" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow font-semibold transition flex items-center">
            <span class="mr-2">➕</span> Tambah Pengguna
        </button>
    </div>

    @if (session()->has('message_user'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('message_user') }}</div>
    @endif
    @if (session()->has('error_user'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error_user') }}</div>
    @endif

    {{-- Form Modal --}}
    @if ($showForm)
    <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-200 mb-6">
        <h4 class="font-bold text-lg mb-4 text-slate-800">
            {{ $editingUserId ? '✏️ Edit Pengguna' : '➕ Tambah Pengguna Baru' }}
        </h4>
        <form wire:submit.prevent="saveUser">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input wire:model="name" type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nama lengkap" required>
                    @if(isset($errors) && $errors->has('name')) <span class="text-red-500 text-xs">{{ $errors->first('name') }}</span> @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                    <input wire:model="email" type="email" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="user@adaptika.id" required>
                    @if(isset($errors) && $errors->has('email')) <span class="text-red-500 text-xs">{{ $errors->first('email') }}</span> @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password {{ $editingUserId ? '(kosongkan jika tidak ingin ubah)' : '' }}</label>
                    <input wire:model="password" type="password" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ $editingUserId ? '••••••••' : 'Minimal 8 karakter' }}" {{ $editingUserId ? '' : 'required' }}>
                    @if(isset($errors) && $errors->has('password')) <span class="text-red-500 text-xs">{{ $errors->first('password') }}</span> @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role / Peran</label>
                    <select wire:model.live="role" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($allRoles as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($role === 'Instruktur Teknis')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <div>
                    <label class="block text-sm font-semibold text-indigo-800 mb-1">🏫 Kejuruan</label>
                    <select wire:model.live="assigned_kejuruan" class="w-full border-indigo-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Kejuruan --</option>
                        @foreach ($allKejuruan as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-indigo-800 mb-1">📋 Program Pelatihan</label>
                    <select wire:model="assigned_program" class="w-full border-indigo-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" {{ empty($assigned_kejuruan) ? 'disabled' : '' }}>
                        <option value="">-- Pilih Program --</option>
                        @foreach ($allProgram as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-indigo-600 mt-1">Pilih kejuruan terlebih dahulu.</p>
                </div>
            </div>
            @endif

            @if ($role === 'Peserta Pelatihan')
            <div class="grid grid-cols-1 gap-4 mb-4 p-4 bg-teal-50 rounded-lg border border-teal-200">
                <div>
                    <label class="block text-sm font-semibold text-teal-800 mb-1">👤 Tautkan ke Data Peserta</label>
                    <select wire:model="peserta_id" class="w-full border-teal-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Pilih Peserta --</option>
                        @foreach ($allPesertas as $peserta)
                            <option value="{{ $peserta->id }}">{{ $peserta->nama }} ({{ $peserta->kejuruan }} - {{ $peserta->program_pelatihan }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-teal-600 mt-1">Gunakan ini agar peserta dapat melihat datanya sendiri saat login.</p>
                </div>
            </div>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg shadow font-semibold transition">
                    💾 {{ $editingUserId ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                </button>
                @if(!$editingUserId)
                <button type="button" wire:click.prevent="createInvitationUser" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg shadow font-semibold transition">
                    🔗 Buat & Dapatkan Link Undangan
                </button>
                @endif
                <button type="button" wire:click="$set('showForm', false)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-semibold transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Modal Link Undangan --}}
    @if ($showInvitationModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-data="{ copied: false }">
        <div class="bg-white rounded-2xl shadow-2xl border border-indigo-100 max-w-lg w-full p-6 relative overflow-hidden">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                        🔗
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-slate-800">Link Undangan & Reset Password</h4>
                        <p class="text-xs text-slate-500">Bagikan link ini langsung ke Instruktur / Pengguna</p>
                    </div>
                </div>
                <button wire:click="closeInvitationModal" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="text-slate-400">Nama:</span> <strong class="text-slate-800">{{ $invitationUserName }}</strong></div>
                    <div><span class="text-slate-400">Email:</span> <strong class="text-slate-800">{{ $invitationUserEmail }}</strong></div>
                    <div><span class="text-slate-400">Role:</span> <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 font-bold">{{ $invitationUserRole }}</span></div>
                    @if($invitationUserKejuruan)
                    <div><span class="text-slate-400">Kejuruan:</span> <strong class="text-indigo-600">{{ $invitationUserKejuruan }}</strong></div>
                    @endif
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-700 mb-1">URL Undangan Aktif (Instruktur Tinggal Set Password)</label>
                <div class="flex items-center space-x-2">
                    <input type="text" readonly value="{{ $invitationUrl }}" class="w-full bg-slate-100 border border-slate-300 rounded-lg text-xs p-2.5 font-mono text-slate-800 focus:outline-none select-all">
                    <button type="button" 
                            x-on:click="navigator.clipboard.writeText('{{ $invitationUrl }}'); copied = true; setTimeout(() => copied = false, 3000)"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-xs font-bold transition flex items-center shrink-0">
                        <span x-show="!copied">📋 Salin Link</span>
                        <span x-show="copied" x-cloak class="text-green-300">✅ Disalin!</span>
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-2">💡 Instruktur yang membuka link di atas dapat langsung membuat password baru tanpa perlu verifikasi email server (SMTP).</p>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" wire:click="closeInvitationModal" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg text-xs font-semibold transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" class="w-full md:w-1/3 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="🔍 Cari nama, email, atau role...">
    </div>

    {{-- Table --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Kejuruan / Program</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $u)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        @php
                            $badgeColors = [
                                'Superadmin' => 'bg-red-100 text-red-800',
                                'Penyelenggara' => 'bg-blue-100 text-blue-800',
                                'Instruktur Teknis' => 'bg-indigo-100 text-indigo-800',
                                'Pengantar Kerja' => 'bg-purple-100 text-purple-800',
                                'Seksi Pemberdayaan' => 'bg-teal-100 text-teal-800',
                                'Kepala Balai' => 'bg-amber-100 text-amber-800',
                                'Peserta Pelatihan' => 'bg-gray-100 text-gray-800',
                            ];
                            $color = $badgeColors[$u->role] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $color }}">{{ $u->role }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        @if($u->assigned_kejuruan)
                            {{ $u->assigned_kejuruan }}
                            @if($u->assigned_program)
                                <br><span class="text-indigo-600">→ {{ $u->assigned_program }}</span>
                            @endif
                        @elseif($u->role === 'Peserta Pelatihan' && $u->peserta)
                            <span class="text-teal-600 font-semibold">Taut: {{ $u->peserta->nama }}</span>
                            <br><span class="text-gray-400">({{ $u->peserta->kejuruan }})</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center space-x-1">
                            <button wire:click="generateInvitationLink({{ $u->id }})" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-semibold transition border border-indigo-200" title="Salin Link Undangan / Reset Password">🔗 Link</button>
                            <button wire:click="openEditForm({{ $u->id }})" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-semibold transition border border-blue-200" title="Edit">✏️</button>
                            <button wire:click="resetPassword({{ $u->id }})" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-xs font-semibold transition border border-yellow-200" title="Reset Password" wire:confirm="Reset password '{{ $u->name }}' ke default?">🔑</button>
                            @if($u->id !== auth()->id())
                            <button wire:click="deleteUser({{ $u->id }})" class="bg-red-50 hover:bg-red-100 text-red-700 px-3 py-1 rounded text-xs font-semibold transition border border-red-200" title="Hapus" wire:confirm="Yakin ingin menghapus user '{{ $u->name }}'?">🗑️</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Tidak ada pengguna ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
