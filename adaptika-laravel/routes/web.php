<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/clean-data', function () {
    \App\Models\Peserta::query()->delete();
    \App\Models\Intervensi::query()->delete();
    if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
        \Illuminate\Support\Facades\DB::table('notifications')->delete();
    }
    return response("✅ SUKSES: Seluruh data peserta dan intervensi telah dibersihkan dari database! Jumlah peserta saat ini: " . \App\Models\Peserta::count(), 200);
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/download/laporan-kesiapan', function (\Illuminate\Http\Request $request) {
    try {
        $kejuruan = $request->query('kejuruan');
        $query = \App\Models\Peserta::query();
        if (!empty($kejuruan)) {
            $query->where('kejuruan', $kejuruan);
        }
        $pesertas = $query->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laporan-kesiapan', [
            'pesertas' => $pesertas
        ]);
        $filename = 'Laporan_Manajerial_ADAPTIKA' . (!empty($kejuruan) ? '_' . str_replace(' ', '_', $kejuruan) : '') . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
        return response("Gagal membuat PDF Laporan: " . $e->getMessage(), 500);
    }
})->middleware(['auth'])->name('download.laporan-kesiapan');

Route::get('/download/career-passport/{peserta}', function (\App\Models\Peserta $peserta) {
    try {
        $prompt = "Nama Peserta: {$peserta->nama}\nKejuruan: {$peserta->kejuruan}\nProfil RIASEC: {$peserta->profil_riasec} ({$peserta->kode_riasec})\nTugas: Buat narasi adaptabilitas positif yang menjual (HR-friendly) tentang bagaimana karakter bawaannya ini membuat dia sukses mempraktikkan skill {$peserta->kejuruan} di dunia kerja, serta rekomendasikan 2 ekosistem industri spesifik yang cocok untuknya.";
        $aiService = new \App\Services\AiEngineService();
        $careerPassport = $aiService->callAiGuardrailed($prompt, "Career Passport");

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.career-passport', [
            'peserta' => $peserta,
            'careerPassport' => $careerPassport
        ]);
        $filename = 'Career_Passport_' . str_replace(' ', '_', $peserta->nama) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Career Passport Export Error: ' . $e->getMessage());
        return response("Gagal membuat Career Passport PDF: " . $e->getMessage(), 500);
    }
})->middleware(['auth'])->name('download.career-passport');

Route::get('/download/template-csv', function () {
    $filename = 'Template_Import_ADAPTIKA.csv';
    $content = "nama,kejuruan,program_pelatihan,skor_logika_numerik,skor_spasial_figural,kode_riasec,profil_riasec,angkatan\n" .
        "Budi Santoso,Teknik Las,Juru Las SMAW,42,38,RSE,Realistic-Social-Enterprising,Batch 1 (Jan-Mar 2026)\n" .
        "Siti Aminah,TIK,Web Programming,85,90,ISA,Investigative-Social-Artistic,Batch 1 (Jan-Mar 2026)\n" .
        "Rudi Hermawan,Otomotif,Teknisi Sepeda Motor,55,62,IRE,Investigative-Realistic-Enterprising,Batch 2 (Apr-Jun 2026)\n";

    return response($content, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
})->middleware(['auth'])->name('download.template-csv');

require __DIR__.'/auth.php';
