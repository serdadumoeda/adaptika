<div class="p-6">
    <h3 class="text-2xl font-bold mb-2">🤝 Dasbor Pemberdayaan: Penempatan Kerja & Inkubasi</h3>
    <p class="text-gray-600 mb-6">Fokus: Memastikan lulusan BPVP disalurkan ke lingkungan industri yang sejalan dengan kapabilitas dan karakter mereka untuk menekan angka resign dini.</p>

    <!-- Form Upload CSV Data Intake -->
    <div class="bg-white shadow-sm rounded-xl p-6 mb-8 border border-gray-100 border-l-4 border-indigo-600">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h4 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                    <span>📤</span> Intake Data Peserta Pelatihan Vokasi (SIAPkerja / SiapLatih)
                </h4>
                <p class="text-sm text-gray-500 mt-1">Unggah file CSV pendaftaran peserta untuk mengkategorikan diagnosis kuadran (K1-K4) dan mendistribusikan data ke Instruktur & Pengantar Kerja.</p>
            </div>
        </div>

        <form wire:submit.prevent="importCsv" class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <input type="file" wire:model="csvFile" accept=".csv,.txt" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-lg p-1.5">
            <button type="submit" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-lg shadow transition whitespace-nowrap disabled:opacity-50">
                <span wire:loading.remove wire:target="importCsv">🚀 Import CSV Data</span>
                <span wire:loading wire:target="importCsv">⏳ Mengunggah & Memproses...</span>
            </button>
            <a href="{{ route('download.template-csv') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg shadow transition whitespace-nowrap inline-flex items-center justify-center gap-1.5 text-sm">
                📥 Unduh Format CSV
            </a>
        </form>
        @error('csvFile') <span class="text-red-500 text-xs block mt-2">{{ $message }}</span> @enderror
    </div>

    <!-- Matriks Penyaluran & Job Matching -->
    <div class="bg-white shadow rounded-lg p-6 border-t-4 border-teal-500">
        <h4 class="font-bold text-lg mb-2">Matriks Penyaluran Tenaga Kerja Strategis (Alumni BPVP)</h4>
        <p class="text-sm text-gray-500 mb-6">Pilih alumni yang telah dinyatakan Kompeten maupun Belum Kompeten oleh Instruktur untuk direkomendasikan ke lingkungan kerja yang paling sesuai.</p>

        @if (session()->has('message_salur'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('message_salur') }}</div>
        @endif

        @if ($pesertaKompeten->isEmpty())
            @if ($totalOwned === 0)
                <div class="bg-gray-50 border-l-4 border-gray-400 p-5 rounded-r shadow-sm flex items-center">
                    <span class="text-2xl mr-3">📭</span>
                    <p class="text-gray-700 font-medium">Belum ada data alumni yang telah dievaluasi oleh Instruktur.</p>
                </div>
            @else
                <div class="bg-teal-50 border-l-4 border-teal-500 p-5 rounded-r shadow-sm flex items-center">
                    <span class="text-2xl mr-3">🎉</span>
                    <p class="text-teal-800 font-medium">Target BPVP Tercapai! Seluruh antrean lulusan telah berhasil disalurkan ke ekosistem industri.</p>
                </div>
            @endif
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Sidebar Antrean -->
                <div class="col-span-1 bg-gray-50 rounded-xl p-4 border border-gray-200 shadow-inner">
                    <h5 class="font-bold mb-4 text-slate-700 flex items-center"><span class="mr-2">📋</span> Alumni Siap Salur</h5>
                    <ul class="divide-y divide-gray-200 max-h-96 overflow-y-auto pr-2">
                        @foreach($pesertaKompeten as $peserta)
                        <li class="py-2">
                            <button wire:click="selectPeserta({{ $peserta->id }})" class="text-left w-full hover:bg-white p-3 rounded-lg transition {{ $pesertaId == $peserta->id ? 'bg-teal-50 border-l-4 border-teal-500 shadow text-teal-900' : 'text-gray-700 hover:shadow-sm' }}">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold">{{ $peserta->nama }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $peserta->status_kelulusan === 'Kompeten' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $peserta->status_kelulusan }}
                                    </span>
                                </div>
                                <div class="text-xs mt-1 text-gray-500">{{ $peserta->kejuruan }}</div>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Panel Tindakan -->
                <div class="lg:col-span-2">
                    @if ($selectedPeserta)
                        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6">
                            <h5 class="font-bold text-xl mb-4 text-slate-800 border-b pb-2">💼 Job Matching: {{ $selectedPeserta->nama }}</h5>
                            
                            <div class="mb-5">
                                <button wire:click="getAiRecommendation" wire:loading.attr="disabled" class="w-full sm:w-auto bg-teal-600 text-white px-5 py-2.5 rounded shadow hover:bg-teal-700 disabled:opacity-50 transition font-semibold">
                                    <span wire:loading.remove wire:target="getAiRecommendation">🤖 Dapatkan Rekomendasi Penempatan (AI)</span>
                                    <span wire:loading wire:target="getAiRecommendation">⏳ Menganalisis profil...</span>
                                </button>
                            </div>

                            @if ($aiRecommendation)
                                <div class="mb-6 p-5 rounded-lg bg-teal-50 border border-teal-200 shadow-sm">
                                    <h5 class="font-bold mb-3 text-teal-900 flex items-center"><span class="mr-2">🧩</span> Analisis Person-Environment Fit:</h5>
                                    <p class="mb-4 text-sm leading-relaxed text-teal-800">{{ $aiRecommendation['analisis'] }}</p>
                                    
                                    <h5 class="font-bold text-teal-900 mt-4 mb-2 flex items-center"><span class="mr-2">🎯</span> Rekomendasi Penyaluran Strategis:</h5>
                                    <div class="bg-white p-4 rounded border border-teal-100 text-sm leading-relaxed text-slate-700 italic shadow-inner">
                                        {!! nl2br(e($aiRecommendation['rekomendasi_aksi'])) !!}
                                    </div>
                                </div>
                            @endif

                            <form wire:submit.prevent="saveIntervensi" class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">📄 Log Surat Keputusan Penyaluran</label>
                                    <textarea wire:model="catatan" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm" placeholder="Misal: Disalurkan secara strategis ke program Inkubasi Wirausaha Mandiri karena tingginya sifat Enterprising..."></textarea>
                                    @error('catatan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" onclick="return confirm('Apakah Anda yakin dengan keputusan penyaluran ini? Keputusan ini bersifat permanen dan akan ditambahkan ke rekam jejak alumni.')" class="w-full bg-green-600 text-white px-4 py-3 rounded shadow hover:bg-green-700 transition font-bold text-sm">
                                    ✅ Simpan & Finalisasi Penyaluran
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 bg-gray-50 rounded-xl border border-gray-200 border-dashed py-16">
                            <span class="text-5xl mb-4">🏢</span>
                            <p class="text-lg font-medium text-gray-500">Pilih alumni untuk memproses Job Matching.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
