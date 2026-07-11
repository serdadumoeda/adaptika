<div class="p-6" x-data="{ viewMode: 'mitigasi' }">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-2xl font-bold">🛠️ Dasbor Instruktur: Manajemen & Evaluasi Kelas</h3>
        <div class="flex bg-gray-200 rounded-lg p-1">
            <button @click="viewMode = 'mitigasi'" :class="viewMode === 'mitigasi' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:bg-gray-300'" class="px-4 py-2 rounded-md text-sm font-bold transition">1. Mitigasi Kendala Belajar</button>
            <button @click="viewMode = 'evaluasi'" :class="viewMode === 'evaluasi' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:bg-gray-300'" class="px-4 py-2 rounded-md text-sm font-bold transition">2. Verifikasi Kelulusan Akhir</button>
        </div>
    </div>
    <p class="text-gray-600 mb-6">Fokus: Memodifikasi teknik pengajaran bengkel untuk peserta dengan indikasi learning gap teknis.</p>

    <!-- Mode Mitigasi Kendala Belajar -->
    <div x-show="viewMode === 'mitigasi'" x-transition>
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        @if ($pesertas->isEmpty())
            @if ($totalOwned === 0)
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4">
                    <p class="text-gray-700">📭 Belum ada data peserta yang masuk ke kelas Anda. Silakan tunggu sinkronisasi data dari penyelenggara.</p>
                </div>
            @else
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <p class="text-green-700">🎉 Luar biasa! Seluruh potensi kendala belajar teknis peserta telah dimitigasi.</p>
                </div>
            @endif
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Sidebar Antrean -->
                <div class="col-span-1 bg-white shadow rounded-lg p-4">
                    <h4 class="font-bold text-lg mb-3">📋 Daftar Antrean</h4>
                    <ul class="divide-y divide-gray-200">
                        @foreach($pesertas as $peserta)
                        <li class="py-3">
                            <button wire:click="selectPeserta({{ $peserta->id }})" class="text-left w-full hover:bg-gray-50 p-2 rounded {{ $pesertaId == $peserta->id ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' }}">
                                <div class="font-semibold text-gray-900">{{ $peserta->nama }}</div>
                                <div class="text-sm text-gray-500">{{ $peserta->diagnosis_awal }}</div>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Panel Tindakan -->
                <div class="col-span-2 bg-white shadow rounded-lg p-6">
                    @if ($selectedPeserta)
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h4 class="font-bold text-xl">Menganalisis: {{ $selectedPeserta->nama }}</h4>
                                <p class="text-gray-500 text-sm">Kejuruan: {{ $selectedPeserta->kejuruan }} {{ $selectedPeserta->program_pelatihan ? '- ' . $selectedPeserta->program_pelatihan : '' }}</p>
                            </div>
                            <div class="px-3 py-1 bg-gray-100 rounded text-sm font-semibold text-gray-700">
                                {{ $selectedPeserta->diagnosis_awal }}
                            </div>
                        </div>

                        <!-- Alpine.js Tabs -->
                        <div x-data="{ activeTab: 1 }">
                            <div class="flex border-b border-gray-200 mb-6">
                                <button @click="activeTab = 1" :class="activeTab === 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors focus:outline-none">
                                    🕸️ Pemetaan Kesiapan Pelatihan
                                </button>
                                <button @click="activeTab = 2" :class="activeTab === 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors focus:outline-none">
                                    🤖 AI Rekomendasi
                                </button>
                            </div>

                            <!-- Tab 1: Profil Kesiapan -->
                            <div x-show="activeTab === 1" class="space-y-6" x-transition>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h5 class="font-semibold text-gray-700 mb-4 text-center">Visualisasi Kognitif (Skor Asli)</h5>
                                        <div class="relative h-64 w-full flex justify-center" wire:ignore>
                                            <canvas id="kognitifChart-{{ $selectedPeserta->id }}"></canvas>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        @php
                                            // Hanya menggunakan data NYATA dari database — tanpa hardcode
                                            $skorNum = (int) $selectedPeserta->skor_logika_numerik;
                                            $skorFig = (int) $selectedPeserta->skor_spasial_figural;
                                            $threshold = (int) \App\Models\Setting::get('threshold_k2', 60);

                                            $kj = strtolower($selectedPeserta->kejuruan ?? '');
                                            $isTeknisPraktis = str_contains($kj, 'las') || str_contains($kj, 'listrik') || str_contains($kj, 'kelistrikan') || str_contains($kj, 'bangunan') || str_contains($kj, 'otomotif');
                                            $isDigital = str_contains($kj, 'web') || str_contains($kj, 'tik') || str_contains($kj, 'programming') || str_contains($kj, 'desain') || str_contains($kj, 'grafis');

                                            // Tentukan dimensi utama per kejuruan
                                            $dimensiKritis = $isTeknisPraktis ? 'Spasial Figural' : ($isDigital ? 'Logika Numerik' : 'Keduanya');
                                            $skorKritis = $isTeknisPraktis ? $skorFig : ($isDigital ? $skorNum : min($skorNum, $skorFig));

                                            $kategoriTertinggi = $skorNum >= $skorFig ? 'Logika Numerik' : 'Spasial Figural';
                                            $nilaiTertinggi = max($skorNum, $skorFig);
                                            $kategoriTerendah = $skorNum < $skorFig ? 'Logika Numerik' : 'Spasial Figural';
                                            $nilaiTerendah = min($skorNum, $skorFig);
                                            $adaGap = $nilaiTerendah < $threshold;
                                        @endphp

                                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r shadow-sm">
                                            <p class="font-bold text-blue-800 flex items-center"><span class="mr-2">💡</span> Kekuatan Kesiapan Pelatihan:</p>
                                            <p class="text-sm text-blue-900 mt-1">Peserta unggul di <strong>{{ $kategoriTertinggi }}</strong> (Skor: {{ $nilaiTertinggi }}). Pendekatan materi disarankan menggunakan kekuatan ini sebagai jangkar belajar.</p>
                                        </div>

                                        <div class="{{ $adaGap ? 'bg-red-50 border-l-4 border-red-500' : 'bg-green-50 border-l-4 border-green-500' }} p-4 rounded-r shadow-sm">
                                            <p class="font-bold {{ $adaGap ? 'text-red-800' : 'text-green-800' }} flex items-center"><span class="mr-2">{{ $adaGap ? '⚠️' : '✅' }}</span> {{ $adaGap ? 'Indikasi Learning Gap Teknis' : 'Tidak Ada Gap Kritis' }}:</p>
                                            <p class="text-sm {{ $adaGap ? 'text-red-900' : 'text-green-900' }} mt-1">
                                                @if($adaGap)
                                                    Skor <strong>{{ $kategoriTerendah }}</strong> ({{ $nilaiTerendah }}) berada di bawah ambang batas ({{ $threshold }}). Dimensi kritis untuk kejuruan ini: <strong>{{ $dimensiKritis }}</strong>.
                                                @else
                                                    Kedua dimensi kognitif berada di atas ambang batas ({{ $threshold }}). Tidak terdeteksi hambatan teknis signifikan.
                                                @endif
                                            </p>
                                        </div>

                                        <div class="bg-gray-50 border border-gray-200 p-3 rounded text-xs text-gray-500">
                                            ℹ️ Dimensi kritis per kejuruan: Teknis Praktis (Las/Listrik/Otomotif) → Spasial Figural. Digital (TIK/Web) → Logika Numerik.
                                        </div>
                                    </div>
                                </div>

                                @script
                                <script>
                                    // Hanya gunakan data NYATA — Logika Numerik & Spasial Figural
                                    setTimeout(() => {
                                        if (window.myKognitifChart) { window.myKognitifChart.destroy(); }
                                        const ctx = document.getElementById('kognitifChart-{{ $selectedPeserta->id }}');
                                        if(ctx) {
                                            const threshold = {{ (int) \App\Models\Setting::get('threshold_k2', 60) }};
                                            const skorNum = {{ (int) $selectedPeserta->skor_logika_numerik }};
                                            const skorFig = {{ (int) $selectedPeserta->skor_spasial_figural }};
                                            window.myKognitifChart = new Chart(ctx.getContext('2d'), {
                                                type: 'bar',
                                                data: {
                                                    labels: ['Logika Numerik', 'Spasial Figural'],
                                                    datasets: [
                                                        {
                                                            label: 'Skor Peserta',
                                                            data: [skorNum, skorFig],
                                                            backgroundColor: [
                                                                skorNum < threshold ? 'rgba(239,68,68,0.7)' : 'rgba(99,102,241,0.7)',
                                                                skorFig < threshold ? 'rgba(239,68,68,0.7)' : 'rgba(99,102,241,0.7)'
                                                            ],
                                                            borderColor: [
                                                                skorNum < threshold ? 'rgba(239,68,68,1)' : 'rgba(99,102,241,1)',
                                                                skorFig < threshold ? 'rgba(239,68,68,1)' : 'rgba(99,102,241,1)'
                                                            ],
                                                            borderWidth: 2,
                                                            borderRadius: 6,
                                                        },
                                                        {
                                                            label: 'Ambang Batas (' + threshold + ')',
                                                            data: [threshold, threshold],
                                                            type: 'line',
                                                            borderColor: 'rgba(234,179,8,0.9)',
                                                            borderWidth: 2,
                                                            borderDash: [6, 3],
                                                            pointRadius: 0,
                                                            fill: false,
                                                        }
                                                    ]
                                                },
                                                options: {
                                                    indexAxis: 'y',
                                                    scales: {
                                                        x: { min: 0, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
                                                        y: { grid: { display: false } }
                                                    },
                                                    plugins: {
                                                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                                                        tooltip: {
                                                            callbacks: {
                                                                label: function(ctx) {
                                                                    if (ctx.dataset.type === 'line') return 'Ambang batas: ' + threshold;
                                                                    const val = ctx.raw;
                                                                    const status = val < threshold ? ' (di bawah ambang)' : ' (aman)';
                                                                    return 'Skor: ' + val + status;
                                                                }
                                                            }
                                                        }
                                                    },
                                                    maintainAspectRatio: false
                                                }
                                            });
                                        }
                                    }, 100);
                                </script>
                                @endscript
                            </div>

                            <!-- Tab 2: AI Rekomendasi & Action -->
                            <div x-show="activeTab === 2" style="display: none;" class="space-y-6" x-transition>
                                <div class="mb-4">
                                    <h5 class="font-bold text-gray-800 mb-2">💡 Mitigasi Kendala Belajar & Pedagogi</h5>
                                    <p class="text-sm text-gray-500 mb-4">Gunakan rekomendasi asisten AI untuk memformulasikan taktik matrikulasi bengkel khusus peserta ini.</p>
                                    
                                    <button wire:click="getAiRecommendation" wire:loading.attr="disabled" class="w-full sm:w-auto bg-indigo-600 text-white px-6 py-2.5 rounded shadow hover:bg-indigo-700 disabled:opacity-50 transition font-medium">
                                        <span wire:loading.remove wire:target="getAiRecommendation">🤖 Bangkitkan Rekomendasi AI</span>
                                        <span wire:loading wire:target="getAiRecommendation">⏳ AI sedang memproses...</span>
                                    </button>
                                </div>

                                @if ($aiRecommendation)
                                    <div class="p-5 rounded-lg {{ $aiRecommendation['tingkat_risiko'] === 'TINGGI' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200' }} shadow-sm">
                                        <h5 class="font-bold mb-3 text-slate-800 flex items-center"><span class="mr-2">🧠</span> Analisis Psikopedagogi AI:</h5>
                                        <p class="mb-4 text-sm leading-relaxed text-slate-700">{{ $aiRecommendation['analisis'] }}</p>
                                        
                                        <h5 class="font-bold text-slate-800 mt-4 mb-2 flex items-center"><span class="mr-2">🎯</span> Taktik Instruksional Khusus:</h5>
                                        <div class="bg-white p-4 rounded border border-gray-100 text-sm leading-relaxed text-slate-700 italic shadow-sm">
                                            {!! nl2br(e($aiRecommendation['rekomendasi_aksi'])) !!}
                                        </div>
                                    </div>
                                @endif

                                <form wire:submit.prevent="saveIntervensi" class="bg-gray-50 p-5 rounded-lg border border-gray-200 mt-6">
                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Log Tindakan Instruktur (Penyesuaian Pedagogis)</label>
                                        <textarea wire:model="catatan" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Misal: Modul disederhanakan, praktik dibagi menjadi langkah-langkah kecil (chunking)..."></textarea>
                                        @error('catatan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin memfinalisasi tindakan ini? Peserta akan dipindahkan dari antrean mitigasi.')" class="w-full bg-green-600 text-white px-4 py-2.5 rounded shadow hover:bg-green-700 transition font-medium">
                                        ✅ Simpan Keputusan & Tandai Selesai
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                            <span class="text-4xl mb-4">👈</span>
                            <p class="text-lg">Pilih peserta dari daftar antrean untuk memulai tindakan.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Mode Evaluasi Kelulusan -->
    <div x-show="viewMode === 'evaluasi'" style="display: none;" x-transition>
        @if (session()->has('message_evaluasi'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('message_evaluasi') }}</div>
        @endif

        @if ($pesertaEvaluasi->isEmpty())
            @if ($totalOwned === 0)
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4">
                    <p class="text-gray-700 font-medium">Belum ada data peserta di kelas Anda.</p>
                </div>
            @else
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <p class="text-blue-700 font-medium">Belum ada peserta yang siap dievaluasi atau seluruh peserta telah diluluskan.</p>
                </div>
            @endif
        @else
            <div class="bg-white shadow rounded-lg p-6 border-t-4 border-blue-500">
                <h4 class="font-bold text-lg mb-2">Verifikasi Kompetensi Akhir</h4>
                <p class="text-sm text-gray-500 mb-6">Pembaruan status kelulusan kompetensi teknis peserta di akhir masa pelatihan bengkel.</p>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Nama Peserta</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Kejuruan</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Diagnosis Awal</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Keputusan Sertifikasi Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($pesertaEvaluasi as $p)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $p->nama }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $p->kejuruan }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span class="text-xs font-bold text-gray-700">{{ $p->diagnosis_awal }}</span>
                                    @if(str_contains($p->diagnosis_awal, 'Eksplorasi') || str_contains($p->diagnosis_awal, 'Perhatian'))
                                        <div class="mt-1 text-[10px] {{ $p->status_pengantar_kerja === 'Sudah Ditangani' ? 'text-green-600' : 'text-orange-500' }}">
                                            {{ $p->status_pengantar_kerja === 'Sudah Ditangani' ? '✅ Konseling Selesai' : '⏳ Menunggu Konseling' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="updateStatusKelulusan({{ $p->id }}, 'Kompeten')" onclick="return confirm('Tetapkan status KOMPETEN untuk {{ $p->nama }}? Tindakan ini permanen.')" class="bg-green-50 hover:bg-green-100 text-green-700 text-xs font-bold px-3 py-1.5 rounded shadow-sm border border-green-200 mr-2 transition">✅ Kompeten (Lulus)</button>
                                    <button wire:click="updateStatusKelulusan({{ $p->id }}, 'Belum Kompeten')" onclick="return confirm('Tetapkan status BELUM KOMPETEN (Gagal) untuk {{ $p->nama }}? Tindakan ini permanen.')" class="bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold px-3 py-1.5 rounded shadow-sm border border-red-200 transition">❌ Belum Kompeten (Gagal)</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
