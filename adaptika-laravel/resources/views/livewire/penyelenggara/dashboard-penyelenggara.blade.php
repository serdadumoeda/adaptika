<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            @if(auth()->user()->role === 'Kepala Balai')
                <h3 class="text-2xl font-bold mb-1">👔 Dashboard Eksekutif Kepala Balai BPVP</h3>
                <p class="text-gray-600">Rangkuman Strategis Kesiapan Vokasional & Pendampingan Peserta Secara Makro</p>
            @else
                <h3 class="text-2xl font-bold mb-1">🏢 Dashboard Penyelenggara BPVP</h3>
                <p class="text-gray-600">Rangkuman Kesehatan Psikologis & Pendampingan Peserta Secara Makro</p>
            @endif
        </div>
        
        @if(!$allKejuruan->isEmpty())
        <div>
            <select wire:model.live="filterKejuruan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">-- Semua Kejuruan --</option>
                @foreach($allKejuruan as $kj)
                    <option value="{{ $kj }}">{{ $kj }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>
    
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if($pesertas->isEmpty())
        <div class="bg-white shadow-sm rounded-xl p-12 text-center border border-gray-100">
            <div class="text-6xl mb-4">📭</div>
            <h4 class="text-xl font-bold text-slate-800 mb-2">Data Kesiapan Kelas Masih Kosong</h4>
            <p class="text-gray-500 mb-6">Belum ada data peserta yang dimasukkan ke dalam sistem. Analitik belum dapat dijalankan.</p>
            <p class="text-sm text-gray-400">Silakan hubungi administrator (Superadmin) untuk menjalankan sinkronisasi data dari SIAP Kerja via menu Pengaturan.</p>
        </div>
        
        <!-- Ekspor Laporan -->
        <div class="bg-gray-50 shadow rounded-lg p-6 border border-gray-200 mt-6 max-w-xl mx-auto">
            <h4 class="font-bold text-lg">🏛️ Laporan Indeks Kesiapan BPVP</h4>
            <p class="text-sm text-gray-500 mb-4">Unduh Laporan PDF komprehensif untuk Kepala Balai dan log CSV untuk audit trail kemnaker.</p>
            <div class="flex space-x-3">
                <a href="{{ route('download.laporan-kesiapan', ['kejuruan' => $filterKejuruan]) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 shadow font-semibold inline-flex items-center gap-1">
                    📥 Unduh PDF Laporan
                </a>
                <button wire:click="downloadCsv" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow font-semibold">
                    📥 Unduh CSV Audit
                </button>
            </div>
        </div>
    @else

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kapasitas Mumpuni (K1)</p>
            <p class="text-3xl font-bold text-slate-800">{{ $statKuadran['1'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Perlu Pendampingan (K2)</p>
            <p class="text-3xl font-bold text-slate-800">{{ $statKuadran['2'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sedang Dieksplorasi (K3)</p>
            <p class="text-3xl font-bold text-slate-800">{{ $statKuadran['3'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Perlu Perhatian Khusus (K4)</p>
            <p class="text-3xl font-bold text-slate-800">{{ $statKuadran['4'] }}</p>
        </div>
    </div>

    <!-- Charts Section 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white shadow-sm rounded-xl p-6 border border-gray-100">
            <h4 class="font-bold text-lg mb-4 text-slate-800">📊 Pemetaan Kesiapan Kelas</h4>
            <div class="relative h-64" wire:ignore>
                <canvas id="kesiapanChart"></canvas>
            </div>
        </div>
        <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
            <h4 class="font-bold text-lg mb-4 text-slate-800">📈 Progress Penanganan</h4>
            <div class="relative h-64" wire:ignore>
                <canvas id="progressChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Section 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
            <h4 class="font-bold text-lg mb-1 text-slate-800">📊 Distribusi Risiko Kesiapan Berdasarkan Kejuruan</h4>
            <p class="text-xs text-gray-500 mb-4">Analisis agregat untuk melihat kejuruan mana yang membutuhkan perhatian khusus.</p>
            <div class="relative h-64" wire:ignore>
                <canvas id="trendKejuruanChart"></canvas>
            </div>
        </div>
        <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
            <h4 class="font-bold text-lg mb-1 text-slate-800">📈 Peta Persebaran Kesiapan Pelatihan Teknis</h4>
            <p class="text-xs text-gray-500 mb-4">Visualisasi penyebaran performa pelatihan (Numerik vs Figural).</p>
            <div class="relative h-64" wire:ignore>
                <canvas id="scatterKognitifChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart Scripts -->
    @script
    <script>
        setTimeout(() => {
            if (window.chart1) window.chart1.destroy();
            const ctx1 = document.getElementById('kesiapanChart');
            if (ctx1) {
                window.chart1 = new Chart(ctx1.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['K1 (Mumpuni)', 'K2 (Pendampingan)', 'K3 (Eksplorasi)', 'K4 (Perhatian)'],
                        datasets: [{
                            label: 'Jumlah Peserta',
                            data: [{{ $statKuadran['1'] }}, {{ $statKuadran['2'] }}, {{ $statKuadran['3'] }}, {{ $statKuadran['4'] }}],
                            backgroundColor: ['#10b981', '#f59e0b', '#f97316', '#ef4444'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            if (window.chart2) window.chart2.destroy();
            const ctx2 = document.getElementById('progressChart');
            if (ctx2) {
                window.chart2 = new Chart(ctx2.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Sudah Ditangani', 'Belum Ditangani'],
                        datasets: [{
                            data: [{{ $statProgress['instruktur_selesai'] }}, {{ $statProgress['instruktur_belum'] }}],
                            backgroundColor: ['#3b82f6', '#cbd5e1']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%'
                    }
                });
            }

            // Parse PHP data to JS
            const pesertas = @json($allPesertas);
            
            // Trend Kejuruan Data Prep
            const kejuruans = [...new Set(pesertas.map(p => p.kejuruan))];
            const datasetAman = [];
            const datasetRisiko = [];
            kejuruans.forEach(kj => {
                let countAman = pesertas.filter(p => p.kejuruan === kj && p.diagnosis_awal.includes('Mumpuni')).length;
                let countRisiko = pesertas.filter(p => p.kejuruan === kj && !p.diagnosis_awal.includes('Mumpuni')).length;
                datasetAman.push(countAman);
                datasetRisiko.push(countRisiko);
            });

            if (window.chart3) window.chart3.destroy();
            const ctx3 = document.getElementById('trendKejuruanChart');
            if (ctx3) {
                window.chart3 = new Chart(ctx3.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: kejuruans.map(k => k.length > 20 ? k.substring(0, 20)+'...' : k),
                        datasets: [
                            { label: 'Kapasitas Mumpuni', data: datasetAman, backgroundColor: '#10b981' },
                            { label: 'Perlu Pendampingan/Intervensi', data: datasetRisiko, backgroundColor: '#f43f5e' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true, beginAtZero: true }
                        }
                    }
                });
            }

            // Scatter Data Prep
            const scatterData = pesertas.map(p => ({
                x: p.skor_logika_numerik,
                y: p.skor_spasial_figural,
                name: p.nama
            }));

            if (window.chart4) window.chart4.destroy();
            const ctx4 = document.getElementById('scatterKognitifChart');
            if (ctx4) {
                window.chart4 = new Chart(ctx4.getContext('2d'), {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Peserta',
                            data: scatterData,
                            backgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { title: { display: true, text: 'Logika Numerik' }, min: 0, max: 100 },
                            y: { title: { display: true, text: 'Spasial Figural' }, min: 0, max: 100 }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const point = context.raw;
                                        return point.name + ' (Num: ' + point.x + ', Fig: ' + point.y + ')';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }, 100);
    </script>
    @endscript

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Log Keputusan Manajerial -->
        <div class="bg-white shadow rounded-lg p-6">
            <h4 class="font-bold text-lg mb-4">⚙️ Kebijakan & Intervensi Manajerial</h4>
            @if(auth()->user()->role === 'Kepala Balai')
                <p class="text-sm text-gray-600 mb-4">Daftar keputusan kebijakan yang telah ditindaklanjuti oleh tim Penyelenggara.</p>
                <div class="space-y-3 max-h-48 overflow-y-auto">
                    @forelse(\App\Models\Intervensi::where('role', 'Penyelenggara')->orderBy('created_at', 'desc')->get() as $log)
                        <div class="p-3 bg-slate-50 border border-gray-100 rounded text-xs">
                            <span class="font-bold text-indigo-600">{{ $log->created_at->format('d M Y H:i') }}</span>: 
                            <span class="italic text-gray-700">{{ $log->catatan }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada keputusan manajerial yang dicatat.</p>
                    @endforelse
                </div>
            @else
                <p class="text-sm text-gray-600 mb-4">Catat intervensi manajerial (contoh: Penyesuaian durasi istirahat, pengadaan modul) untuk menjaga kesehatan mental peserta secara umum.</p>
                
                <form wire:submit.prevent="saveKeputusan">
                    <textarea wire:model="catatanManajerial" rows="4" class="w-full border-gray-300 rounded-md shadow-sm mb-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ketik tindakan manajemen di sini..."></textarea>
                    @if(isset($errors) && $errors->has('catatanManajerial'))
                        <span class="text-red-500 text-xs block mb-2">{{ $errors->first('catatanManajerial') }}</span>
                    @endif
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 w-full font-semibold">Simpan Keputusan Manajerial</button>
                </form>
            @endif
        </div>

        <!-- Rekapitulasi Komprehensif (Sesuai Tab 3 Streamlit) -->
        <div class="col-span-1 md:col-span-2 bg-white shadow-sm rounded-xl p-6 border border-gray-100 mt-6 overflow-hidden">
            <h4 class="font-bold text-lg mb-2 text-slate-800">📑 Rekapitulasi Historis Peserta</h4>
            <p class="text-sm text-gray-500 mb-4">Pantau pergerakan intervensi untuk audit internal BPVP (Bisa di-scroll horizontal untuk data lengkap).</p>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Kejuruan</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Diagnosis Awal</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Instruktur</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Pengantar Kerja</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Kelulusan</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Penyaluran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($pesertas as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $p->nama }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->kejuruan }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $p->program_pelatihan ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ str_contains($p->diagnosis_awal, 'Mumpuni') ? 'bg-green-100 text-green-800' : 
                                      (str_contains($p->diagnosis_awal, 'Pendampingan') ? 'bg-yellow-100 text-yellow-800' : 
                                      (str_contains($p->diagnosis_awal, 'Eksplorasi') ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ Str::limit($p->diagnosis_awal, 25) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($p->status_instruktur == 'Sudah Ditangani')
                                    <span class="text-green-600">✅</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($p->status_pengantar_kerja == 'Sudah Ditangani')
                                    <span class="text-green-600">✅</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($p->status_kelulusan == 'Kompeten')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Kompeten</span>
                                @elseif($p->status_kelulusan == 'Belum Kompeten')
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Belum Kompeten</span>
                                @else
                                    <span class="text-gray-400 text-xs">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($p->status_pemberdayaan == 'Sudah Disalurkan')
                                    <span class="px-2 py-1 text-xs rounded-full bg-teal-100 text-teal-800">Disalurkan</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $pesertas->links() }}
    </div>
    
    <!-- Ekspor Laporan -->
    <div class="bg-gray-50 shadow rounded-lg p-6 border border-gray-200 mt-6 max-w-xl mx-auto">
        <h4 class="font-bold text-lg">🏛️ Laporan Indeks Kesiapan BPVP</h4>
        <p class="text-sm text-gray-500 mb-4">Unduh Laporan PDF komprehensif untuk Kepala Balai dan log CSV untuk audit trail kemnaker.</p>
        <div class="flex space-x-3">
            <a href="{{ route('download.laporan-kesiapan', ['kejuruan' => $filterKejuruan]) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 shadow font-semibold inline-flex items-center gap-1">
                📥 Unduh PDF Laporan
            </a>
            <button wire:click="downloadCsv" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow font-semibold">
                📥 Unduh CSV Audit
            </button>
            <a href="/Template_Import_ADAPTIKA.csv" download="Template_Import_ADAPTIKA.csv" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700 shadow font-semibold inline-flex items-center gap-1">
                📥 Format CSV
            </a>
        </div>
    </div>
    @endif
</div>
