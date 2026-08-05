<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kesiapan BPVP</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 20px; margin-bottom: 30px; }
        .header img { width: 60px; height: auto; }
        .header h1 { margin: 10px 0 5px 0; color: #0f172a; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 0; color: #64748b; font-size: 14px; }
        
        .section-title { background-color: #0f172a; color: white; padding: 8px 15px; font-size: 14px; margin-top: 30px; margin-bottom: 15px; }
        
        .summary-box { border: 1px solid #cbd5e1; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .summary-box h3 { margin-top: 0; font-size: 16px; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .summary-item { margin-bottom: 8px; }
        .summary-item span.label { font-weight: bold; display: inline-block; width: 250px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 10px; border: 1px solid #cbd5e1; }
        td { padding: 10px; border: 1px solid #cbd5e1; }
        tr:nth-child(even) { background-color: #f8fafc; }
        
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .badge-k1 { background-color: #d1fae5; color: #065f46; } /* Aman */
        .badge-k2 { background-color: #fef3c7; color: #92400e; } /* Gap */
        .badge-k3 { background-color: #ffedd5; color: #9a3412; } /* Risiko */
        .badge-k4 { background-color: #fee2e2; color: #991b1b; } /* Krisis */
        
        .footer { margin-top: 50px; text-align: right; font-size: 12px; color: #64748b; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@php
    $logoKemnakerPath = null;
    $possiblePaths = [
        resource_path('views/pdf/logo-kemnaker.png'),
        public_path('logo-kemnaker.png'),
        base_path('public/logo-kemnaker.png'),
        __DIR__ . '/logo-kemnaker.png',
    ];
    foreach ($possiblePaths as $p) {
        if (file_exists($p)) {
            $logoKemnakerPath = $p;
            break;
        }
    }
@endphp

    <div class="header">
        @if(extension_loaded('gd') && $logoKemnakerPath)
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoKemnakerPath)) }}" style="height: 70px; margin-bottom: 12px;" alt="Logo Kemnaker">
        @else
            <div style="display: inline-block; background-color: #0f172a; color: #ffffff; padding: 6px 16px; border-radius: 4px; font-weight: bold; font-size: 11px; letter-spacing: 1px; margin-bottom: 12px; text-transform: uppercase;">
                KEMENTERIAN KETENAGAKERJAAN RI &bull; BPVP
            </div>
        @endif
        <h1>Laporan Indeks Kesiapan BPVP</h1>
        <p>ADAPTIKA - Human-Centric & Psychological Analytics System</p>
        <p>Tanggal Ekspor: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }}</p>
    </div>

    <div class="summary-box">
        <h3>Ringkasan Eksekutif Pemetaan Kesiapan Kelas</h3>
        <div class="summary-item"><span class="label">Total Peserta Terdaftar:</span> {{ $pesertas->count() }} orang</div>
        <div class="summary-item"><span class="label">K1 (Kapasitas Mumpuni):</span> {{ $pesertas->where('diagnosis_awal', 'Kuadran 1 (Kapasitas Mumpuni)')->count() }} orang</div>
        <div class="summary-item"><span class="label">K2 (Perlu Pendampingan):</span> {{ $pesertas->where('diagnosis_awal', 'Kuadran 2 (Perlu Pendampingan)')->count() }} orang</div>
        <div class="summary-item"><span class="label">K3 (Sedang Dieksplorasi):</span> {{ $pesertas->where('diagnosis_awal', 'Kuadran 3 (Sedang Dieksplorasi)')->count() }} orang</div>
        <div class="summary-item"><span class="label">K4 (Perlu Perhatian Khusus):</span> {{ $pesertas->where('diagnosis_awal', 'Kuadran 4 (Perlu Perhatian Khusus)')->count() }} orang</div>
    </div>

    <div class="section-title">Rekapitulasi Historis Peserta & Intervensi</div>
    
    <table>
        <thead>
            <tr>
                <th>Nama Peserta</th>
                <th>Kejuruan</th>
                <th>Diagnosis Kesiapan Awal</th>
                <th>Instruktur</th>
                <th>Pengantar Kerja</th>
                <th>Status Kelulusan</th>
                <th>Status Penyaluran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pesertas as $p)
                @php
                    $badgeClass = 'badge-k1';
                    if(str_contains($p->diagnosis_awal, 'Pendampingan')) $badgeClass = 'badge-k2';
                    elseif(str_contains($p->diagnosis_awal, 'Eksplorasi')) $badgeClass = 'badge-k3';
                    elseif(str_contains($p->diagnosis_awal, 'Perhatian')) $badgeClass = 'badge-k4';
                @endphp
                <tr>
                    <td><strong>{{ $p->nama }}</strong><br><span style="font-size:10px; color:#64748b;">RIASEC: {{ $p->kode_riasec }}</span></td>
                    <td>{{ $p->kejuruan }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $p->diagnosis_awal }}</span></td>
                    <td style="color: {{ $p->status_instruktur == 'Sudah Ditangani' ? '#16a34a' : '#94a3b8' }}">{{ $p->status_instruktur }}</td>
                    <td style="color: {{ $p->status_pengantar_kerja == 'Sudah Ditangani' ? '#16a34a' : '#94a3b8' }}">{{ $p->status_pengantar_kerja }}</td>
                    <td>
                        <span class="badge {{ $p->status_kelulusan === 'Kompeten' ? 'badge-k1' : ($p->status_kelulusan === 'Belum Kompeten' ? 'badge-k4' : '') }}">
                            {{ $p->status_kelulusan }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $p->status_pemberdayaan === 'Sudah Disalurkan' ? 'badge-k1' : '' }}">
                            {{ $p->status_pemberdayaan }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="section-title">Jejak Audit & Keputusan Manajerial (Audit Trail)</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Waktu</th>
                <th width="15%">Role</th>
                <th width="70%">Log Tindakan / Catatan Intervensi</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\App\Models\Intervensi::orderBy('created_at', 'desc')->get() as $log)
                <tr>
                    <td style="font-size:10px;">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ strtoupper($log->role) }}</strong></td>
                    <td style="font-size:11px; font-style:italic;">{{ $log->catatan }}</td>
                </tr>
            @endforeach
            @if(\App\Models\Intervensi::count() == 0)
                <tr><td colspan="3" style="text-align:center; color:#94a3b8;">Belum ada log keputusan manajerial.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Dicetak secara otomatis oleh Sistem ADAPTIKA BPVP<br>
        Dokumen ini sah tanpa tanda tangan basah.
    </div>

</body>
</html>
