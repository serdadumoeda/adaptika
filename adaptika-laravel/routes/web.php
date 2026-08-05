<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

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

require __DIR__.'/auth.php';
