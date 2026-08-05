<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold">🏫 Manajemen Kejuruan & Program Pelatihan</h3>
            <p class="text-gray-600">Atur kategori kejuruan utama dan program-program pelatihan teknis yang diselenggarakan oleh BPVP.</p>
        </div>
        <button wire:click="openCreateKejuruan" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow font-semibold transition flex items-center">
            <span class="mr-2">➕</span> Tambah Kejuruan
        </button>
    </div>

    @if (session()->has('message_kj'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('message_kj') }}</div>
    @endif

    {{-- Form Kejuruan --}}
    @if ($showKejuruanForm)
    <div class="bg-white shadow-md rounded-xl p-6 border border-gray-200 mb-6">
        <h4 class="font-bold text-lg mb-4 text-slate-800">
            {{ $editingKejuruanId ? '✏️ Edit Kejuruan' : '➕ Tambah Kejuruan Baru' }}
        </h4>
        <form wire:submit.prevent="saveKejuruan" class="flex items-end space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kejuruan</label>
                <input wire:model="kejuruanNama" type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: Teknik Las, TIK, Otomotif" required>
                @if(isset($errors) && $errors->has('kejuruanNama')) <span class="text-red-500 text-xs">{{ $errors->first('kejuruanNama') }}</span> @endif
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow font-semibold transition">
                    💾 Simpan
                </button>
                <button type="button" wire:click="$set('showKejuruanForm', false)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded font-semibold transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Form Program --}}
    @if ($showProgramForm)
    <div class="bg-white shadow-md rounded-xl p-6 border border-gray-200 mb-6">
        <h4 class="font-bold text-lg mb-4 text-slate-800">
            {{ $editingProgramId ? '✏️ Edit Program Pelatihan' : '➕ Tambah Program Pelatihan' }}
        </h4>
        <form wire:submit.prevent="saveProgram" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Program Pelatihan</label>
                <input wire:model="programNama" type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: Juru Las SMAW, Web Programming" required>
                @if(isset($errors) && $errors->has('programNama')) <span class="text-red-500 text-xs">{{ $errors->first('programNama') }}</span> @endif
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Program (Opsional / dari SIAP Kerja)</label>
                <input wire:model="programKode" type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: TL-SMAW-01">
                @if(isset($errors) && $errors->has('programKode')) <span class="text-red-500 text-xs">{{ $errors->first('programKode') }}</span> @endif
            </div>
            <div class="col-span-1 md:col-span-2 flex items-center space-x-3 mt-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg shadow font-semibold transition">
                    💾 Simpan Program
                </button>
                <button type="button" wire:click="$set('showProgramForm', false)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-semibold transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Grid Kejuruan & Program --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($kejuruans as $kj)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="bg-slate-50 border-b border-gray-200 p-4 flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-lg text-slate-800">{{ $kj->nama }}</h4>
                    <p class="text-xs text-gray-500">{{ $kj->programs->count() }} Program Pelatihan</p>
                </div>
                <div class="flex space-x-1">
                    <button wire:click="openEditKejuruan({{ $kj->id }})" class="text-blue-600 hover:bg-blue-50 p-1.5 rounded transition text-xs" title="Edit Kejuruan">✏️</button>
                    <button wire:click="deleteKejuruan({{ $kj->id }})" class="text-red-600 hover:bg-red-50 p-1.5 rounded transition text-xs" title="Hapus Kejuruan" wire:confirm="Yakin ingin menghapus kejuruan '{{ $kj->nama }}' beserta program di dalamnya?">🗑️</button>
                </div>
            </div>
            
            <div class="p-4 flex-1 flex flex-col justify-between">
                <div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @forelse ($kj->programs as $p)
                        <li class="py-2.5 flex justify-between items-center hover:bg-gray-50 px-1 rounded transition">
                            <div>
                                <span class="font-medium text-gray-800">{{ $p->nama }}</span>
                                @if($p->kode_program)
                                    <br><span class="text-xs font-mono text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $p->kode_program }}</span>
                                @endif
                            </div>
                            <div class="flex space-x-1">
                                <button wire:click="openEditProgram({{ $p->id }})" class="text-blue-500 hover:text-blue-700 text-xs px-1.5" title="Edit">✏️</button>
                                <button wire:click="deleteProgram({{ $p->id }})" class="text-red-500 hover:text-red-700 text-xs px-1.5" title="Hapus" wire:confirm="Hapus program '{{ $p->nama }}'?">🗑️</button>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-center text-gray-400 italic">Belum ada program pelatihan.</li>
                        @endforelse
                    </ul>
                </div>
                
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <button wire:click="openCreateProgram({{ $kj->id }})" class="w-full text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold py-2 rounded-lg border border-indigo-100 transition">
                        ➕ Tambah Program Pelatihan
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
            <span class="text-4xl">🏫</span>
            <p class="mt-2 text-lg font-medium">Belum ada kejuruan terdaftar.</p>
            <p class="text-sm mt-1">Gunakan tombol Tambah Kejuruan atau Sinkronisasi dari SIAP Kerja.</p>
        </div>
        @endforelse
    </div>
</div>
