<?php

namespace App\Livewire\Instruktur;

use Livewire\Component;

class DashboardInstruktur extends Component
{
    public $pesertaId;
    public $aiRecommendation = null;
    public $catatan = '';
    public $filterKejuruan = '';
    public $filterProgram = '';

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('access-instruktur');
    }

    public function mount()
    {
        $this->filterKejuruan = auth()->user()->assigned_kejuruan ?? '';
        $this->filterProgram = auth()->user()->assigned_program ?? '';
    }

    /**
     * Helper: pastikan peserta yang di-akses berada di scope kejuruan/program instruktur ini.
     */
    private function findOwnedPeserta($id)
    {
        $query = \App\Models\Peserta::where('id', $id);
        if ($this->filterKejuruan) {
            $query->where('kejuruan', $this->filterKejuruan);
        }
        if ($this->filterProgram) {
            $query->where('program_pelatihan', $this->filterProgram);
        }
        return $query->first();
    }

    public function render()
    {
        // 1. Peserta yang butuh mitigasi awal teknis (K2 dan K4)
        $queryAntrean = \App\Models\Peserta::where('status_instruktur', 'Belum Ditangani')
                        ->where(function ($q) {
                            $q->where('diagnosis_awal', 'like', '%Pendampingan%')
                              ->orWhere('diagnosis_awal', 'like', '%Perhatian%');
                        });

        // 2. Peserta yang siap dievaluasi kelulusannya
        // K1: Langsung siap
        // K2: Setelah Instruktur selesai
        // K3: Setelah Pengantar Kerja selesai
        // K4: Setelah KEDUANYA selesai
        $queryEvaluasi = \App\Models\Peserta::where('status_kelulusan', 'Belum Dievaluasi')
                        ->where(function ($q) {
                            // K1 (Mumpuni)
                            $q->where('diagnosis_awal', 'like', '%Mumpuni%')
                            // K2 (Pendampingan)
                              ->orWhere(function ($q2) {
                                  $q2->where('diagnosis_awal', 'like', '%Pendampingan%')
                                     ->where('status_instruktur', 'Sudah Ditangani');
                              })
                            // K3 (Eksplorasi)
                              ->orWhere(function ($q3) {
                                  $q3->where('diagnosis_awal', 'like', '%Eksplorasi%')
                                     ->where('status_pengantar_kerja', 'Sudah Ditangani');
                              })
                            // K4 (Perhatian Khusus)
                              ->orWhere(function ($q4) {
                                  $q4->where('diagnosis_awal', 'like', '%Perhatian%')
                                     ->where('status_instruktur', 'Sudah Ditangani')
                                     ->where('status_pengantar_kerja', 'Sudah Ditangani');
                              });
                        });

        if ($this->filterKejuruan) {
            $queryAntrean->where('kejuruan', $this->filterKejuruan);
            $queryEvaluasi->where('kejuruan', $this->filterKejuruan);
        }

        if ($this->filterProgram) {
            $queryAntrean->where('program_pelatihan', $this->filterProgram);
            $queryEvaluasi->where('program_pelatihan', $this->filterProgram);
        }

        $pesertas = $queryAntrean->get();
        $pesertaEvaluasi = $queryEvaluasi->get();

        $totalOwnedPesertaQuery = \App\Models\Peserta::query();
        if ($this->filterKejuruan) {
            $totalOwnedPesertaQuery->where('kejuruan', $this->filterKejuruan);
        }
        if ($this->filterProgram) {
            $totalOwnedPesertaQuery->where('program_pelatihan', $this->filterProgram);
        }
        $totalOwned = $totalOwnedPesertaQuery->count();

        $selectedPeserta = $this->pesertaId ? $this->findOwnedPeserta($this->pesertaId) : null;

        return view('livewire.instruktur.dashboard-instruktur', [
            'pesertas' => $pesertas,
            'pesertaEvaluasi' => $pesertaEvaluasi,
            'selectedPeserta' => $selectedPeserta,
            'totalOwned' => $totalOwned,
        ]);
    }

    public function updateStatusKelulusan($id, $status)
    {
        $peserta = $this->findOwnedPeserta($id);
        if ($peserta) {
            $peserta->status_kelulusan = $status;
            $peserta->save();
            
            \App\Models\Intervensi::create([
                'user_id' => auth()->id(),
                'peserta_id' => $peserta->id,
                'role' => 'Instruktur Teknis',
                'catatan' => 'Sertifikasi Akhir: Dinyatakan ' . $status
            ]);
            
            session()->flash('message_evaluasi', "Peserta {$peserta->nama} berhasil dievaluasi sebagai {$status}.");
            $this->reset(['pesertaId', 'aiRecommendation', 'catatan']);
        }
    }

    public function selectPeserta($id)
    {
        $this->pesertaId = $id;
        $this->aiRecommendation = null;
        $this->catatan = '';
    }

    public function getAiRecommendation()
    {
        $peserta = $this->findOwnedPeserta($this->pesertaId);
        if (!$peserta) return;

        $prompt = "Peserta {$peserta->nama} mengikuti kelas praktik {$peserta->kejuruan}. Ia sangat kesulitan di area Spasial Figural/Logika Numerik, NAMUN memiliki keunggulan lain. Tugas: Berikan 1 teknik instruksional yang SANGAT PERSONAL dan UNIK. Jangan gunakan contoh klise.";
        
        $riwayat = \App\Models\Intervensi::where('peserta_id', $peserta->id)->orderBy('created_at', 'asc')->get()->map(function($i) {
            return "[{$i->role}]: {$i->catatan}";
        })->toArray();

        $aiService = new \App\Services\AiEngineService();
        $this->aiRecommendation = $aiService->callAiGuardrailed($prompt, "Instruktur Teknis", $riwayat);
    }

    public function saveIntervensi()
    {
        $this->validate(['catatan' => 'required|min:5']);
        $peserta = $this->findOwnedPeserta($this->pesertaId);
        if (!$peserta) return;
        
        \App\Models\Intervensi::create([
            'user_id' => auth()->id(),
            'peserta_id' => $peserta->id,
            'role' => 'Instruktur Teknis',
            'catatan' => $this->catatan
        ]);

        $peserta->status_instruktur = 'Sudah Ditangani';
        $peserta->catatan_instruktur = 'Instruktur: ' . $this->catatan;
        $peserta->save();

        session()->flash('message', 'Tindakan berhasil disimpan.');
        $this->reset(['pesertaId', 'aiRecommendation', 'catatan']);
    }
}
