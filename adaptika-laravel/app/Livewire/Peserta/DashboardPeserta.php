<?php

namespace App\Livewire\Peserta;

use Livewire\Component;

class DashboardPeserta extends Component
{
    public $peserta;
    public $careerPassport = null;

    public function boot()
    {
        // Peserta dashboard bisa diakses oleh role Peserta dan Superadmin (untuk preview)
        $user = auth()->user();
        if ($user->role !== 'Peserta Pelatihan' && $user->role !== 'Superadmin') {
            abort(403, 'Akses ditolak.');
        }
    }

    public function mount()
    {
        $user = auth()->user();
        if ($user->role === 'Superadmin') {
            $this->peserta = \App\Models\Peserta::first();
        } elseif ($user->peserta_id) {
            $this->peserta = \App\Models\Peserta::find($user->peserta_id);
        } else {
            $this->peserta = \App\Models\Peserta::where('nama', $user->name)->first();
        }
    }

    public function render()
    {
        return view('livewire.peserta.dashboard-peserta');
    }

    public function generatePassport()
    {
        if (!$this->peserta) return;

        $prompt = "Nama Peserta: {$this->peserta->nama}\nKejuruan: {$this->peserta->kejuruan}\nProfil RIASEC: {$this->peserta->profil_riasec} ({$this->peserta->kode_riasec})\nTugas: Buat narasi adaptabilitas positif yang menjual (HR-friendly) tentang bagaimana karakter bawaannya ini membuat dia sukses mempraktikkan skill {$this->peserta->kejuruan} di dunia kerja, serta rekomendasikan 2 ekosistem industri spesifik yang cocok untuknya.";
        
        $aiService = new \App\Services\AiEngineService();
        $this->careerPassport = $aiService->callAiGuardrailed($prompt, "Career Passport");
    }

    public function downloadPassportPdf()
    {
        if (!$this->careerPassport) return;
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.career-passport', [
            'peserta' => $this->peserta,
            'careerPassport' => $this->careerPassport
        ]);
        
        return response()->streamDownload(fn() => print($pdf->output()), 'Career_Passport_' . str_replace(' ', '_', $this->peserta->nama) . '.pdf');
    }
}
