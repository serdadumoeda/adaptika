<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 text-gray-900">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">Profil 360° & Audit Timeline: {{ $peserta->nama }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Profil Singkat -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Informasi Asesmen</h4>
                <div class="space-y-2">
                    <p class="text-sm"><span class="font-medium text-gray-500 w-32 inline-block">Kejuruan:</span> {{ $peserta->kejuruan }} ({{ $peserta->program_pelatihan }})</p>
                    <p class="text-sm"><span class="font-medium text-gray-500 w-32 inline-block">Diagnosis Awal:</span> <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-md text-xs font-bold">{{ $peserta->diagnosis_awal }}</span></p>
                    <p class="text-sm"><span class="font-medium text-gray-500 w-32 inline-block">Status Kelulusan:</span> 
                        @if($peserta->status_kelulusan === 'Kompeten')
                            <span class="text-green-600 font-bold">Kompeten</span>
                        @elseif($peserta->status_kelulusan === 'Belum Kompeten')
                            <span class="text-red-600 font-bold">Belum Kompeten</span>
                        @else
                            <span class="text-gray-500">{{ $peserta->status_kelulusan }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Skor Kognitif & Kepribadian -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Skor & Kepribadian</h4>
                <div class="flex flex-col space-y-3">
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium">Logika Numerik</span>
                            <span>{{ $peserta->skor_logika_numerik }}/100</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $peserta->skor_logika_numerik }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium">Spasial Figural</span>
                            <span>{{ $peserta->skor_spasial_figural }}/100</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ $peserta->skor_spasial_figural }}%"></div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <p class="text-xs text-gray-500">Tipe Kepribadian (RIASEC):</p>
                        <p class="text-sm font-bold text-indigo-700">{{ $peserta->kode_riasec }} <span class="font-normal text-gray-600">({{ $peserta->profil_riasec }})</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vertical Timeline -->
        <div>
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-6">Jejak Audit (Timeline Intervensi)</h4>
            
            <div class="relative border-l border-gray-200 ml-3 space-y-8">
                <!-- Log Entry for Import (System) -->
                <div class="relative pl-6">
                    <span class="absolute -left-[5px] top-1 h-3 w-3 rounded-full bg-gray-300 ring-4 ring-white"></span>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-medium">{{ $peserta->created_at->format('d M Y, H:i') }}</span>
                        <h5 class="text-sm font-bold text-gray-900 mt-0.5">Sistem ADAPTIKA</h5>
                        <p class="text-sm text-gray-600 mt-1">Data peserta diimpor. Terdiagnosis otomatis masuk dalam antrean <span class="font-semibold">{{ $peserta->diagnosis_awal }}</span>.</p>
                    </div>
                </div>

                <!-- Database Logs -->
                @forelse($intervensis as $log)
                    <div class="relative pl-6">
                        @php
                            $dotColor = 'bg-blue-400';
                            if($log->role === 'Pengantar Kerja') $dotColor = 'bg-yellow-500';
                            if($log->role === 'Instruktur Teknis') $dotColor = 'bg-green-500';
                            if($log->role === 'Seksi Pemberdayaan') $dotColor = 'bg-purple-500';
                        @endphp
                        <span class="absolute -left-[5px] top-1 h-3 w-3 rounded-full {{ $dotColor }} ring-4 ring-white"></span>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 font-medium">{{ $log->created_at->format('d M Y, H:i') }}</span>
                            <h5 class="text-sm font-bold text-gray-900 mt-0.5">Tindakan oleh {{ $log->role }}</h5>
                            <div class="text-sm text-gray-600 mt-1 bg-gray-50 p-3 rounded-md border border-gray-100 shadow-sm whitespace-pre-wrap">{{ $log->catatan }}</div>
                        </div>
                    </div>
                @empty
                    <div class="relative pl-6">
                        <span class="absolute -left-[5px] top-1 h-3 w-3 rounded-full bg-gray-200 ring-4 ring-white"></span>
                        <div class="flex flex-col">
                            <p class="text-sm text-gray-500 italic mt-1">Belum ada tindakan intervensi yang dicatat.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
