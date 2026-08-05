<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Career Passport</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #4F46E5; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 10px 0 5px 0; color: #4F46E5; font-size: 28px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 0; color: #6b7280; font-size: 14px; }
        
        .profile-box { background-color: #f3f4f6; border-left: 5px solid #4F46E5; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0; }
        .profile-box h3 { margin-top: 0; font-size: 18px; color: #1f2937; margin-bottom: 15px; }
        .profile-item { margin-bottom: 8px; }
        .profile-item span.label { font-weight: bold; display: inline-block; width: 200px; color: #4b5563; }
        
        .section-title { font-size: 18px; font-weight: bold; color: #4F46E5; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        
        .content-box { margin-bottom: 30px; text-align: justify; }
        
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 20px; }
    </style>
</head>
<body>

    <div class="header">
        @if(file_exists(public_path('logo-kemnaker.png')))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo-kemnaker.png'))) }}" style="height: 65px; margin-bottom: 10px;" alt="Logo Kemnaker">
        @endif
        <h1>Career Passport</h1>
        <p>Suplemen Kompetensi Talenta & Rekomendasi Vokasional</p>
    </div>

    <div class="profile-box">
        <h3>Profil Profesional</h3>
        <div class="profile-item"><span class="label">Nama Lengkap:</span> {{ $peserta->nama }}</div>
        <div class="profile-item"><span class="label">Fokus Kejuruan:</span> {{ $peserta->kejuruan }}</div>
        <div class="profile-item"><span class="label">Karakter Bawaan (RIASEC):</span> {{ $peserta->profil_riasec }}</div>
    </div>

    <div class="section-title">Narasi Kekuatan (HR-Friendly)</div>
    <div class="content-box">
        <p>{{ $careerPassport['narasi_kekuatan'] ?? 'Data belum tersedia. Silakan generate Career Passport melalui tombol di dashboard.' }}</p>
    </div>

    <div class="section-title">Rekomendasi Ekosistem Industri Strategis</div>
    <div class="content-box">
        <p>{!! nl2br(e($careerPassport['rekomendasi_ekosistem'] ?? 'Data belum tersedia.')) !!}</p>
    </div>

    <div class="footer">
        Diterbitkan secara otomatis oleh Sistem ADAPTIKA BPVP<br>
        Dokumen ini sah sebagai suplemen pendamping Curriculum Vitae (CV).<br>
        Tanggal Terbit: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
    </div>

</body>
</html>
