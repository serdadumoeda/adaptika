<?php

namespace App\Livewire\Pemberdayaan;

use Livewire\Component;

class DashboardPemberdayaan extends Component
{
    public $pesertaId;
    public $aiRecommendation = null;
    public $catatan = '';
    public $statusKelulusan = 'Kompeten';

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('access-pemberdayaan');
    }

    public function render()
    {
        $pesertaKompeten = \App\Models\Peserta::whereIn('status_kelulusan', ['Kompeten', 'Belum Kompeten'])
                            ->where('status_pemberdayaan', '!=', 'Sudah Disalurkan')->get();

        $totalOwned = \App\Models\Peserta::whereIn('status_kelulusan', ['Kompeten', 'Belum Kompeten'])->count();

        $selectedPeserta = $this->pesertaId ? $this->findScopedPeserta($this->pesertaId) : null;

        return view('livewire.pemberdayaan.dashboard-pemberdayaan', [
            'pesertaKompeten' => $pesertaKompeten,
            'selectedPeserta' => $selectedPeserta,
            'totalOwned' => $totalOwned,
        ]);
    }

    /**
     * Hanya akses peserta yang berstatus Kompeten/Belum Kompeten dan belum disalurkan.
     */
    private function findScopedPeserta($id)
    {
        return \App\Models\Peserta::where('id', $id)
            ->whereIn('status_kelulusan', ['Kompeten', 'Belum Kompeten'])
            ->where('status_pemberdayaan', '!=', 'Sudah Disalurkan')
            ->first();
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

        $statusLulus = strtoupper($peserta->status_kelulusan);
        $prompt = "Alumni {$peserta->nama} berstatus {$statusLulus} (Kejuruan {$peserta->kejuruan}). Profil karakter bawaan (RIASEC): {$peserta->kode_riasec} ({$peserta->profil_riasec}). Tugas Anda: Putuskan 'Person-Environment Fit' TERBAIK. Jika Kompeten, pilih salah satu dari: 1. Penempatan Pabrik, 2. Pemagangan/OJT, atau 3. Inkubasi Wirausaha. Jika Belum Kompeten, prioritaskan Inkubasi Wirausaha Mandiri atau program Pendampingan Pelatihan Ulang. Berikan justifikasi analitis yang matang.";
        
        $riwayat = \App\Models\Intervensi::where('peserta_id', $peserta->id)->orderBy('created_at', 'asc')->get()->map(function($i) {
            return "[{$i->role}]: {$i->catatan}";
        })->toArray();

        $aiService = new \App\Services\AiEngineService();
        $this->aiRecommendation = $aiService->callAiGuardrailed($prompt, "Seksi Pemberdayaan", $riwayat);
    }

    public function saveIntervensi()
    {
        $this->validate(['catatan' => 'required|min:5']);
        $peserta = $this->findScopedPeserta($this->pesertaId);
        if (!$peserta) return;
        
        \App\Models\Intervensi::create([
            'user_id' => auth()->id(),
            'peserta_id' => $peserta->id,
            'role' => 'Seksi Pemberdayaan',
            'catatan' => $this->catatan
        ]);

        $peserta->status_pemberdayaan = 'Sudah Disalurkan';
        $peserta->catatan_pemberdayaan = 'Penyaluran: ' . $this->catatan;
        $peserta->save();

        session()->flash('message_salur', 'Keputusan penyaluran berhasil ditetapkan.');
        $this->reset(['pesertaId', 'aiRecommendation', 'catatan']);
    }
}
