<?php

namespace App\Livewire\Pengantar;

use Livewire\Component;

class DashboardPengantar extends Component
{
    public $pesertaId;
    public $aiRecommendation = null;
    public $catatan = '';

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('access-pengantar');
    }

    public function render()
    {
        $pesertas = \App\Models\Peserta::where('status_pengantar_kerja', 'Belum Ditangani')
                        ->where(function ($q) {
                            $q->where('diagnosis_awal', 'like', '%Eksplorasi%')
                              ->orWhere('diagnosis_awal', 'like', '%Perhatian%');
                        })->get();

        $totalOwned = \App\Models\Peserta::where(function ($q) {
            $q->where('diagnosis_awal', 'like', '%Eksplorasi%')
              ->orWhere('diagnosis_awal', 'like', '%Perhatian%');
        })->count();

        $selectedPeserta = $this->pesertaId ? $this->findScopedPeserta($this->pesertaId) : null;

        return view('livewire.pengantar.dashboard-pengantar', [
            'pesertas' => $pesertas,
            'selectedPeserta' => $selectedPeserta,
            'totalOwned' => $totalOwned,
        ]);
    }

    /**
     * Hanya akses peserta yang berada di antrean Pengantar Kerja (K3/K4).
     */
    private function findScopedPeserta($id)
    {
        return \App\Models\Peserta::where('id', $id)
            ->where(function ($q) {
                $q->where('diagnosis_awal', 'like', '%Eksplorasi%')
                  ->orWhere('diagnosis_awal', 'like', '%Perhatian%');
            })->first();
    }

    public function selectPeserta($id)
    {
        $this->pesertaId = $id;
        $this->aiRecommendation = null;
        $this->catatan = '';
    }

    public function getAiRecommendation()
    {
        $peserta = $this->findScopedPeserta($this->pesertaId);
        if (!$peserta) return;

        $prompt = "Peserta {$peserta->nama} memiliki profil RIASEC {$peserta->profil_riasec}. Berikan 2 pertanyaan konseling yang tepat untuk memvalidasi minatnya.";
        
        $riwayat = \App\Models\Intervensi::where('peserta_id', $peserta->id)->orderBy('created_at', 'asc')->get()->map(function($i) {
            return "[{$i->role}]: {$i->catatan}";
        })->toArray();

        $aiService = new \App\Services\AiEngineService();
        $this->aiRecommendation = $aiService->callAiGuardrailed($prompt, "Pengantar Kerja", $riwayat);
    }

    public function saveIntervensi()
    {
        $this->validate(['catatan' => 'required|min:5']);
        $peserta = $this->findScopedPeserta($this->pesertaId);
        if (!$peserta) return;
        
        \App\Models\Intervensi::create([
            'user_id' => auth()->id(),
            'peserta_id' => $peserta->id,
            'role' => 'Pengantar Kerja',
            'catatan' => $this->catatan
        ]);

        $peserta->status_pengantar_kerja = 'Sudah Ditangani';
        $peserta->catatan_pengantar_kerja = 'Pengantar Kerja: ' . $this->catatan;
        $peserta->save();

        // Notifikasi ke Instruktur bahwa Konseling Selesai
        $instrukturs = \App\Models\User::where('role', 'Instruktur Teknis')->get();
        \Illuminate\Support\Facades\Notification::send($instrukturs, new \App\Notifications\SistemNotification(
            'Konseling Peserta Selesai',
            "Peserta {$peserta->nama} telah selesai dikonseling dan kini siap untuk dievaluasi kelulusannya.",
            '/dashboard'
        ));

        session()->flash('message', 'Tindakan konseling berhasil disimpan.');
        $this->reset(['pesertaId', 'aiRecommendation', 'catatan']);
    }
}
