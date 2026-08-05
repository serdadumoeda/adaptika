<div class="p-6">
    <div class="mb-6">
        <h3 class="text-2xl font-bold">⚙️ Pengaturan Aplikasi</h3>
        <p class="text-gray-600">Konfigurasi endpoint API SIAP Kerja Kemnaker, manajemen database, dan metode masukan data sistem.</p>
    </div>

    @if (session()->has('message_settings'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm transition">
            {{ session('message_settings') }}
        </div>
    @endif

    <div x-data="{ tab: 'general' }" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Menu Navigasi Pengaturan -->
        <div class="col-span-1 space-y-2">
            <button @click="tab = 'general'" :class="tab === 'general' ? 'bg-indigo-600 text-white font-semibold' : 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200'" class="w-full text-left px-4 py-3 rounded-xl shadow-sm text-sm transition flex items-center">
                <span class="mr-3">🔗</span> Konektivitas & Intake
            </button>
            <button @click="tab = 'thresholds'" :class="tab === 'thresholds' ? 'bg-indigo-600 text-white font-semibold' : 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200'" class="w-full text-left px-4 py-3 rounded-xl shadow-sm text-sm transition flex items-center">
                <span class="mr-3">📊</span> Ambang Batas Kognitif
            </button>
            <button @click="tab = 'prompts'" :class="tab === 'prompts' ? 'bg-indigo-600 text-white font-semibold' : 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200'" class="w-full text-left px-4 py-3 rounded-xl shadow-sm text-sm transition flex items-center">
                <span class="mr-3">🧠</span> Prompt Sistem AI
            </button>
            <button @click="tab = 'maintenance'" :class="tab === 'maintenance' ? 'bg-indigo-600 text-white font-semibold' : 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200'" class="w-full text-left px-4 py-3 rounded-xl shadow-sm text-sm transition flex items-center">
                <span class="mr-3">🛠️</span> Pemeliharaan Data
            </button>
        </div>

        <!-- Panel Form Utama -->
        <div class="lg:col-span-3">
            <form wire:submit.prevent="saveSettings" class="space-y-6">
                
                {{-- TAB: GENERAL --}}
                <div x-show="tab === 'general'" class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-4" x-transition>
                    <h4 class="font-bold text-lg text-slate-800 flex items-center mb-2">
                        <span class="mr-2">🔗</span> Kredensial API SIAP Kerja
                    </h4>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">SIAP Kerja API URL</label>
                        <input wire:model="apiUrl" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="https://api.siapkerja.kemnaker.go.id" required>
                        @if(isset($errors) && $errors->has('apiUrl')) <span class="text-red-500 text-xs">{{ $errors->first('apiUrl') }}</span> @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">SIAP Kerja API Key (Bearer Token)</label>
                        <input wire:model="apiKey" type="password" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Token API keamanan">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika ingin berjalan dalam Mode Simulasi (Mock Data).</p>
                        @if(isset($errors) && $errors->has('apiKey')) <span class="text-red-500 text-xs">{{ $errors->first('apiKey') }}</span> @endif
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <h4 class="font-bold text-lg text-slate-800 flex items-center mb-4">
                            <span class="mr-2">📤</span> Metode Masukan Data Peserta (Penyelenggara)
                        </h4>
                        <div class="space-y-3">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" wire:model="modeIntake" value="all" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 mt-1">
                                <span class="text-sm text-gray-800">
                                    <strong>Semua Metode (CSV & API):</strong>
                                    <br><span class="text-xs text-gray-500">Penyelenggara bebas mengunggah file CSV manual atau sinkronisasi data instan dari SIAP Kerja.</span>
                                </span>
                            </label>
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" wire:model="modeIntake" value="api_only" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 mt-1">
                                <span class="text-sm text-gray-800">
                                    <strong>Hanya Sinkronisasi API:</strong>
                                    <br><span class="text-xs text-gray-500">Sembunyikan unggahan CSV. Hanya ijinkan pengambilan terpusat demi integrasi data yang valid.</span>
                                </span>
                            </label>
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" wire:model="modeIntake" value="csv_only" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 mt-1">
                                <span class="text-sm text-gray-800">
                                    <strong>Hanya Unggah CSV Mandiri:</strong>
                                    <br><span class="text-xs text-gray-500">Sembunyikan tombol sinkronisasi API. Berguna jika sistem pusat Kemnaker sedang maintenance.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- TAB: THRESHOLDS --}}
                <div x-show="tab === 'thresholds'" style="display:none;" class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-6" x-transition>
                    <div>
                        <h4 class="font-bold text-lg text-slate-800 flex items-center mb-1">
                            <span class="mr-2">📊</span> Ambang Batas Kognitif (Diagnosis Kuadran)
                        </h4>
                        <p class="text-xs text-gray-500">Sesuaikan rentang batas nilai logika numerik dan spasial figural untuk menentukan kuadran kesiapan secara dinamis.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                            <label class="block text-xs font-bold uppercase tracking-wider text-green-800 mb-2">Kuadran 1 (Mumpuni)</label>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-green-700">&gt;</span>
                                <input wire:model="thresholdK1" type="number" class="w-20 border-green-300 bg-white rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm text-center font-bold text-green-950" min="0" max="100">
                                <span class="text-xs text-green-700">Skor</span>
                            </div>
                            <p class="text-2xs text-green-600/80 mt-2">Batas minimal untuk dianggap memiliki kapasitas mumpuni (Logika & Spasial).</p>
                        </div>

                        <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-100">
                            <label class="block text-xs font-bold uppercase tracking-wider text-yellow-800 mb-2">Kuadran 2 (Learning Gap)</label>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-yellow-700">&lt;</span>
                                <input wire:model="thresholdK2" type="number" class="w-20 border-yellow-300 bg-white rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500 text-sm text-center font-bold text-yellow-950" min="0" max="100">
                                <span class="text-xs text-yellow-700">Skor</span>
                            </div>
                            <p class="text-2xs text-yellow-600/80 mt-2">Batas minimal kemampuan logika yang harus dikembangkan lewat pedagogi khusus.</p>
                        </div>

                        <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                            <label class="block text-xs font-bold uppercase tracking-wider text-red-800 mb-2">Kuadran 4 (Krisis Ganda)</label>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-red-700">&lt;</span>
                                <input wire:model="thresholdK4" type="number" class="w-20 border-red-300 bg-white rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 text-sm text-center font-bold text-red-950" min="0" max="100">
                                <span class="text-xs text-red-700">Skor</span>
                            </div>
                            <p class="text-2xs text-red-600/80 mt-2">Batas bawah krisis di mana peserta terdeteksi sangat berpotensi dropout.</p>
                        </div>
                    </div>
                </div>

                {{-- TAB: PROMPTS --}}
                <div x-show="tab === 'prompts'" style="display:none;" class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-6" x-transition>
                    <div>
                        <h4 class="font-bold text-lg text-slate-800 flex items-center mb-1">
                            <span class="mr-2">🧠</span> Prompt Sistem Generatif AI
                        </h4>
                        <p class="text-xs text-gray-500">Modifikasi system prompt (persona instruksi AI) untuk menyesuaikan ketajaman analisis RAG dan gaya narasi Career Passport.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">1. AI Career Passport (CV Suplemen)</label>
                            <textarea wire:model="promptPassport" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs font-mono" placeholder="Instruksi untuk pembentukan dokumen resume..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">2. AI Instruktur Teknis (Tindakan Bengkel)</label>
                            <textarea wire:model="promptInstruktur" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs font-mono" placeholder="Instruksi untuk taktik instruksional bengkel..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">3. AI Pengantar Kerja (Coaching Konseling)</label>
                            <textarea wire:model="promptPengantar" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs font-mono" placeholder="Instruksi untuk pertanyaan eksploratif coaching..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">4. AI Seksi Pemberdayaan (Matching Penyaluran)</label>
                            <textarea wire:model="promptPemberdayaan" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs font-mono" placeholder="Instruksi untuk keselarasan industri strategis..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- TAB: MAINTENANCE --}}
                <div x-show="tab === 'maintenance'" style="display:none;" class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-6" x-transition>
                    <h4 class="font-bold text-lg text-slate-800 flex items-center mb-2">
                        <span class="mr-2">🛠️</span> Penanganan & Pemeliharaan Database
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 rounded-xl border border-gray-200 hover:border-indigo-200 transition">
                            <h5 class="font-bold text-slate-800 text-sm mb-1 flex items-center">
                                <span class="mr-2">🔄</span> Sinkronisasi API Massal
                            </h5>
                            <p class="text-xs text-gray-500 mb-4">Tarik paksa pembaruan data program, kejuruan, dan data peserta secara menyeluruh saat ini.</p>
                            <button type="button" wire:click="triggerSync" wire:loading.attr="disabled" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg shadow transition text-xs flex items-center">
                                <span wire:loading.remove wire:target="triggerSync">Jalankan Sinkronisasi Massal</span>
                                <span wire:loading wire:target="triggerSync">Tunggu, sedang diproses...</span>
                            </button>
                        </div>

                        <div class="p-5 rounded-xl border border-gray-200 hover:border-red-200 transition">
                            <h5 class="font-bold text-red-800 text-sm mb-1 flex items-center">
                                <span class="mr-2">🗑️</span> Bersihkan Database
                            </h5>
                            <p class="text-xs text-gray-500 mb-4">Hapus permanen semua data peserta, log kelulusan, dan riwayat tindakan pendampingan.</p>
                            <button type="button" wire:click="resetDatabase" wire:confirm="Seluruh data peserta dan log tindakan intervensi akan dihapus permanen. Lanjutkan?" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold py-2 px-4 rounded-lg transition text-xs">
                                Hapus Seluruh Data Peserta
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Tombol Simpan -->
                <div x-show="tab !== 'maintenance'" class="flex items-center space-x-3 bg-slate-50 p-4 rounded-xl border border-gray-200">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg shadow font-semibold transition text-sm">
                        💾 Simpan Pengaturan
                    </button>
                    <p class="text-xs text-gray-500">Perubahan pengaturan akan segera diterapkan ke seluruh dasbor operator.</p>
                </div>

            </form>
        </div>
    </div>
</div>
