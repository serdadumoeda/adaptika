<div class="p-6">
    <h3 class="text-2xl font-bold mb-4">🧠 Dasbor Pengantar Kerja: Pendampingan Minat & Karier</h3>
    <p class="text-gray-600 mb-6">Fokus: Memonitor peserta dengan benturan minat kerja yang berpotensi memicu demotivasi belajar di BPVP.</p>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if ($pesertas->isEmpty())
        @if ($totalOwned === 0)
            <div class="bg-gray-50 border-l-4 border-gray-400 p-4">
                <p class="text-gray-700">📭 Belum ada data peserta yang membutuhkan layanan konseling. Silakan tunggu sinkronisasi data dari penyelenggara.</p>
            </div>
        @else
            <div class="bg-green-50 border-l-4 border-green-500 p-4">
                <p class="text-green-700">🎉 Sempurna! Seluruh keselarasan minat dan motivasi belajar peserta telah termonitor dengan baik.</p>
            </div>
        @endif
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Sidebar Antrean -->
            <div class="col-span-1 bg-white shadow rounded-lg p-4">
                <h4 class="font-bold text-lg mb-3">📋 Daftar Antrean Konseling</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach($pesertas as $peserta)
                    <li class="py-3">
                        <button wire:click="selectPeserta({{ $peserta->id }})" class="text-left w-full hover:bg-gray-50 p-2 rounded {{ $pesertaId == $peserta->id ? 'bg-purple-50 border-l-4 border-purple-500' : '' }}">
                            <div class="font-semibold text-gray-900">{{ $peserta->nama }}</div>
                            <div class="text-sm text-gray-500 mb-1">{{ $peserta->diagnosis_awal }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                @if($peserta->status_instruktur === 'Sudah Ditangani')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        ✅ Instruktur Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        ⏳ Instruktur Belum
                                    </span>
                                @endif
                            </div>
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
                            <h4 class="font-bold text-xl">Profil RIASEC: {{ $selectedPeserta->nama }}</h4>
                            <p class="text-gray-500 text-sm">Kejuruan: {{ $selectedPeserta->kejuruan }}</p>
                        </div>
                        <div class="px-3 py-1 bg-purple-100 text-purple-800 rounded text-sm font-semibold">
                            {{ $selectedPeserta->kode_riasec }}
                        </div>
                    </div>

                    <!-- Alpine.js Tabs -->
                    <div x-data="{ activeTab: 1 }">
                        <div class="flex border-b border-gray-200 mb-6">
                            <button @click="activeTab = 1" :class="activeTab === 1 ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors focus:outline-none">
                                📋 Profil RIASEC
                            </button>
                            <button @click="activeTab = 2" :class="activeTab === 2 ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors focus:outline-none">
                                💡 Konseling Motivasi
                            </button>
                        </div>

                        <!-- Tab 1: Profil RIASEC -->
                        <div x-show="activeTab === 1" class="space-y-6" x-transition>
                            @php
                                $riasecCode = strtoupper($selectedPeserta->kode_riasec);
                                if (str_contains($riasecCode, 'C') || str_contains($riasecCode, 'E')) {
                                    $labelsPekerjaan = "['Konsultan Keuangan', 'Administrasi', 'Analis Bisnis']";
                                    $nilaiPekerjaan = "[40, 35, 25]";
                                    $minatText = "Mencari lingkungan kerja yang terstruktur dengan aturan bisnis yang jelas.";
                                    $rekList = "1. Konsultan Keuangan\n2. Administrasi\n3. Analis Bisnis";
                                } elseif (str_contains($riasecCode, 'R')) {
                                    $labelsPekerjaan = "['Teknisi Mesin Presisi', 'Supervisor Lapangan', 'Inspektur']";
                                    $nilaiPekerjaan = "[45, 30, 25]";
                                    $minatText = "Lebih produktif berinteraksi dengan benda fisik, alat, dan mesin.";
                                    $rekList = "1. Teknisi Mesin Presisi\n2. Supervisor Lapangan\n3. Inspektur";
                                } elseif (str_contains($riasecCode, 'I')) {
                                    $labelsPekerjaan = "['Analis Sistem', 'Peneliti Terapan', 'Software Engineer']";
                                    $nilaiPekerjaan = "[50, 30, 20]";
                                    $minatText = "Sangat analitis dan menyukai pemecahan masalah teknis mendalam.";
                                    $rekList = "1. Analis Sistem\n2. Peneliti Terapan\n3. Software Engineer";
                                } else {
                                    $labelsPekerjaan = "['Spesialis Operasional', 'Koordinator Tim', 'Fasilitator']";
                                    $nilaiPekerjaan = "[35, 35, 30]";
                                    $minatText = "Suka berkolaborasi dalam tim dan membantu operasional berjalan lancar.";
                                    $rekList = "1. Spesialis Operasional\n2. Koordinator Tim\n3. Fasilitator";
                                }
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-semibold text-gray-700 mb-4 text-center">Prediksi Person-Environment Fit (SPI)</h5>
                                    <div class="relative h-64 w-full flex justify-center" wire:ignore>
                                        <canvas id="riasecPie-{{ $selectedPeserta->id }}"></canvas>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded shadow-sm">
                                        <p class="font-bold text-blue-800 text-sm">Faktor Pendorong Motivasi:</p>
                                        <p class="text-sm text-blue-900 mt-1">{{ $minatText }}</p>
                                    </div>
                                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded shadow-sm">
                                        <p class="font-bold text-yellow-800 text-sm">Rekomendasi Lingkungan Kerja Alami:</p>
                                        <p class="text-sm text-yellow-900 mt-1 whitespace-pre-line">{{ $rekList }}</p>
                                    </div>
                                    @if($selectedPeserta->status_instruktur == 'Sudah Ditangani' && $selectedPeserta->catatan_instruktur)
                                    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded shadow-sm">
                                        <p class="font-bold text-indigo-800 text-sm">Catatan Instruktur Teknis (Tindakan Sebelumnya):</p>
                                        <p class="text-sm text-indigo-950 mt-1 italic">"{{ str_replace('Instruktur:', '', $selectedPeserta->catatan_instruktur) }}"</p>
                                    </div>
                                    @endif
                                    <p class="text-sm text-gray-600 italic">Profil kepribadian dominan adalah <strong>{{ $selectedPeserta->profil_riasec }}</strong>.</p>
                                </div>
                            </div>

                            @script
                            <script>
                                setTimeout(() => {
                                    if (window.myPieChart) { window.myPieChart.destroy(); }
                                    const ctxPie = document.getElementById('riasecPie-{{ $selectedPeserta->id }}');
                                    if(ctxPie) {
                                        window.myPieChart = new Chart(ctxPie.getContext('2d'), {
                                            type: 'doughnut',
                                            data: {
                                                labels: {!! $labelsPekerjaan !!},
                                                datasets: [{
                                                    data: {!! $nilaiPekerjaan !!},
                                                    backgroundColor: ['#8b5cf6', '#a855f7', '#d946ef'],
                                                    borderWidth: 2,
                                                    borderColor: '#fff'
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: {size: 10} } } },
                                                cutout: '50%'
                                            }
                                        });
                                    }
                                }, 100);
                            </script>
                            @endscript
                        </div>

                        <!-- Tab 2: Konseling Motivasi -->
                        <div x-show="activeTab === 2" style="display: none;" class="space-y-6" x-transition>
                            <div class="mb-4">
                                <h5 class="font-bold text-gray-800 mb-2">🧠 Konseling Mindset & Motivasi</h5>
                                <p class="text-sm text-gray-500 mb-4">Gunakan asisten AI untuk memformulasikan jembatan motivasi antara kejuruan saat ini dengan profil RIASEC bawaan.</p>
                                
                                <button wire:click="getAiRecommendation" wire:loading.attr="disabled" class="w-full sm:w-auto bg-purple-600 text-white px-6 py-2.5 rounded shadow hover:bg-purple-700 disabled:opacity-50 transition font-medium">
                                    <span wire:loading.remove wire:target="getAiRecommendation">🎯 Bangkitkan Pertanyaan Konseling (AI)</span>
                                    <span wire:loading wire:target="getAiRecommendation">⏳ AI sedang memproses...</span>
                                </button>
                            </div>

                            @if ($aiRecommendation)
                                <div class="p-5 rounded-lg {{ $aiRecommendation['tingkat_risiko'] === 'TINGGI' ? 'bg-red-50 border border-red-200' : 'bg-purple-50 border border-purple-200' }} shadow-sm">
                                    <h5 class="font-bold mb-3 text-slate-800 flex items-center"><span class="mr-2">🧠</span> Hasil Analisis Minat AI:</h5>
                                    <p class="mb-4 text-sm leading-relaxed text-slate-700">{{ $aiRecommendation['analisis'] }}</p>
                                    
                                    <h5 class="font-bold text-slate-800 mt-4 mb-2 flex items-center"><span class="mr-2">🎯</span> Pertanyaan Eksploratif (Coaching):</h5>
                                    <div class="bg-white p-4 rounded border border-gray-100 text-sm leading-relaxed text-slate-700 italic shadow-sm">
                                        {!! nl2br(e($aiRecommendation['rekomendasi_aksi'])) !!}
                                    </div>
                                </div>
                            @endif

                            <form wire:submit.prevent="saveIntervensi" class="bg-gray-50 p-5 rounded-lg border border-gray-200 mt-6">
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Log Tindakan Pengantar Kerja (Konseling)</label>
                                    <textarea wire:model="catatan" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm" placeholder="Misal: Membangun jembatan motivasi dengan menjelaskan bahwa kejuruan ini bisa menjadi pijakan untuk buka usaha mandiri..."></textarea>
                                    @error('catatan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" onclick="return confirm('Apakah Anda yakin sesi konseling telah selesai? Tindakan ini akan memindahkan peserta dari antrean konseling.')" class="w-full bg-green-600 text-white px-4 py-2.5 rounded shadow hover:bg-green-700 transition font-medium">
                                    ✅ Simpan Sesi Konseling & Tandai Selesai
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                        <span class="text-4xl mb-4">👈</span>
                        <p class="text-lg">Pilih peserta untuk memulai sesi konseling.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
