<div class="p-6">
    <h3 class="text-2xl font-bold mb-4">🌟 ADAPTIKA Career Passport Personal Hub</h3>
    <p class="text-gray-600 mb-8">Eksklusif untuk Anda. Unduh suplemen kompetensi Anda sebagai bekal profesional ke dunia industri.</p>

    @if(!$peserta)
        <p class="text-red-500">Data profil Anda belum tersedia.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-indigo-50 rounded-lg p-6 border border-indigo-200">
                <h4 class="font-bold text-lg mb-4 text-indigo-900">Profil Anda</h4>
                <p class="mb-2"><strong>Nama:</strong> {{ $peserta->nama }}</p>
                <p class="mb-2"><strong>Kompetensi:</strong> {{ $peserta->kejuruan }}</p>
                <p><strong>Karakter Dominan:</strong> {{ $peserta->profil_riasec }}</p>
            </div>
            
            <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                <h4 class="font-bold text-lg mb-4 text-green-900">Status Kesiapan Terkini</h4>
                @if(str_contains($peserta->diagnosis_awal ?? '', 'Mumpuni'))
                    <p class="text-xl font-bold text-green-700">✅ Kapasitas Mumpuni — Siap Kerja</p>
                    <p class="text-sm mt-3 text-green-800 italic">Anda dinilai memiliki potensi daya juang yang sangat baik untuk terjun ke industri!</p>
                @elseif(str_contains($peserta->diagnosis_awal ?? '', 'Pendampingan'))
                    <p class="text-xl font-bold text-yellow-700">⚡ Dalam Proses Pengembangan</p>
                    <p class="text-sm mt-3 text-yellow-800 italic">Tim instruktur sedang menyiapkan metode pembelajaran khusus untuk memaksimalkan potensi Anda.</p>
                @elseif(str_contains($peserta->diagnosis_awal ?? '', 'Eksplorasi'))
                    <p class="text-xl font-bold text-orange-700">🧭 Sedang Dieksplorasi</p>
                    <p class="text-sm mt-3 text-orange-800 italic">Pengantar Kerja akan membantu menemukan jembatan motivasi antara minat bawaan Anda dan kejuruan ini.</p>
                @else
                    <p class="text-xl font-bold text-blue-700">🔄 Dalam Pemantauan Intensif</p>
                    <p class="text-sm mt-3 text-blue-800 italic">Tim pendamping sedang menyiapkan strategi terbaik untuk mendukung perjalanan pelatihan Anda.</p>
                @endif
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 border-t-4 border-yellow-500">
            <h4 class="font-bold text-lg mb-4">📄 Suplemen Kompetensi Talenta</h4>
            <p class="text-sm text-gray-600 mb-6">Sistem AI kami dapat merangkum potensi kekuatan adaptabilitas (Career Adaptability) Anda berdasarkan asesmen untuk dilampirkan pada Curriculum Vitae (CV) Anda.</p>

            <button wire:click="generatePassport" wire:loading.attr="disabled" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded shadow disabled:opacity-50">
                <span wire:loading.remove wire:target="generatePassport">✨ Generate Dokumen Career Passport (AI)</span>
                <span wire:loading wire:target="generatePassport">⏳ Merumuskan narasi kekuatan profesional...</span>
            </button>

            @if($careerPassport)
                <div class="mt-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                    <h5 class="font-bold text-lg mb-3">Narasi Kekuatan (HR-Friendly):</h5>
                    <p class="mb-6 text-gray-800 leading-relaxed">{{ $careerPassport['narasi_kekuatan'] ?? 'Data tidak tersedia' }}</p>
                    
                    <h5 class="font-bold text-lg mb-3">Rekomendasi Ekosistem Kerja:</h5>
                    <p class="text-gray-800 leading-relaxed mb-6">{!! nl2br(e($careerPassport['rekomendasi_ekosistem'] ?? 'Data tidak tersedia')) !!}</p>
                    
                    <button wire:click="downloadPassportPdf" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 font-semibold w-full">
                        📥 Download Dokumen PDF (Siap Cetak)
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
