<?php

namespace App\Livewire\Penyelenggara;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DashboardPenyelenggara extends Component
{
    use WithFileUploads, WithPagination;

    public $catatanManajerial = '';
    public $csvFile;
    public $filterKejuruan = '';

    public function updatedFilterKejuruan()
    {
        $this->resetPage();
    }

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('access-penyelenggara');
    }

    /**
     * Helper: pastikan hanya Penyelenggara/Superadmin yang bisa melakukan aksi mutasi.
     * Kepala Balai memiliki akses read-only.
     */
    private function assertWriteAccess(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('write-penyelenggara');
    }

    public function render()
    {
        // ──────────────────────────────────────────────────
        // FIX #3: Hitung statistik via SQL COUNT — bukan PHP
        // collection filter. Jauh lebih efisien untuk dataset besar.
        // ──────────────────────────────────────────────────
        $baseQuery = \App\Models\Peserta::query();
        if ($this->filterKejuruan !== '') {
            $baseQuery->where('kejuruan', $this->filterKejuruan);
        }

        // Total peserta (single COUNT query)
        $totalPeserta = (clone $baseQuery)->count();

        // Stat kuadran: 4 COUNT query dengan filter diagnosis_awal
        $statKuadran = [
            '1' => (clone $baseQuery)->where('diagnosis_awal', 'like', '%Mumpuni%')->count(),
            '2' => (clone $baseQuery)->where('diagnosis_awal', 'like', '%Pendampingan%')->count(),
            '3' => (clone $baseQuery)->where('diagnosis_awal', 'like', '%Eksplorasi%')->count(),
            '4' => (clone $baseQuery)->where('diagnosis_awal', 'like', '%Perhatian%')->count(),
        ];

        // Stat progress instruktur: 2 COUNT query
        $statProgress = [
            'instruktur_selesai' => (clone $baseQuery)->where('status_instruktur', 'Sudah Ditangani')->count(),
            'instruktur_belum'   => (clone $baseQuery)->where('status_instruktur', 'Belum Ditangani')->count(),
        ];

        // allKejuruan untuk filter dropdown
        $allKejuruan = \App\Models\Kejuruan::orderBy('nama')->pluck('nama');

        // Paginated untuk tabel rekapitulasi (hanya data yang ditampilkan per halaman)
        $pesertas = (clone $baseQuery)->orderBy('nama')->paginate(15);

        // allPesertas diperlukan oleh blade chart — ambil kolom minimal saja
        $allPesertas = (clone $baseQuery)->select('nama', 'kejuruan', 'diagnosis_awal', 'status_instruktur', 'skor_logika_numerik', 'skor_spasial_figural')->get();

        $modeIntake = \App\Models\Setting::get('mode_intake', 'all');

        return view('livewire.penyelenggara.dashboard-penyelenggara', [
            'pesertas'      => $pesertas,
            'totalPeserta'  => $totalPeserta,
            'statKuadran'   => $statKuadran,
            'statProgress'  => $statProgress,
            'allKejuruan'   => $allKejuruan,
            'allPesertas'   => $allPesertas,
            'modeIntake'    => $modeIntake,
        ]);
    }

    public function saveKeputusan()
    {
        // FIX #2: Guard backend — Kepala Balai tidak boleh menyimpan keputusan
        $this->assertWriteAccess();

        $this->validate(['catatanManajerial' => 'required|min:5']);

        \App\Models\Intervensi::create([
            'user_id'   => auth()->id(),
            'peserta_id' => null,
            'role'      => 'Penyelenggara',
            'catatan'   => $this->catatanManajerial
        ]);

        session()->flash('message', 'Keputusan manajerial berhasil disimpan ke Log Sistem!');
        $this->reset('catatanManajerial');
    }

    public function downloadPdf()
    {
        // PDF boleh diakses Kepala Balai (read-only action)
        $query = \App\Models\Peserta::query();
        if ($this->filterKejuruan) {
            $query->where('kejuruan', $this->filterKejuruan);
        }
        $pesertas = $query->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laporan-kesiapan', [
            'pesertas' => $pesertas
        ]);

        $filename = 'Laporan_Manajerial_ADAPTIKA' . ($this->filterKejuruan ? '_' . str_replace(' ', '_', $this->filterKejuruan) : '') . '.pdf';
        return response()->streamDownload(fn() => print($pdf->output()), $filename);
    }

    public function importCsv()
    {
        // FIX #2: Guard backend — Kepala Balai tidak boleh import data
        $this->assertWriteAccess();

        $this->validate([
            'csvFile' => 'required|file|max:10240',
        ]);

        $ext = strtolower($this->csvFile->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            $this->addError('csvFile', 'Format file harus berupa .csv atau .txt');
            return;
        }

        $realPath = $this->csvFile->getRealPath();
        if (empty($realPath) || !file_exists($realPath) || is_dir($realPath)) {
            $filename = 'import_' . time() . '.' . $ext;
            $path = $this->csvFile->storeAs('csv_imports', $filename);
            $realPath = storage_path('app/private/' . $path);
            if (!file_exists($realPath) || is_dir($realPath)) {
                $realPath = storage_path('app/' . $path);
            }
        }

        (new \App\Jobs\ImportPesertaCsvJob($realPath))->handle();

        session()->flash('message', 'File CSV berhasil diunggah dan data peserta berhasil di-import ke sistem!');
        $this->reset('csvFile');
        return redirect()->route('dashboard');
    }

    public function downloadCsv()
    {
        // CSV audit trail boleh diakses Kepala Balai (read-only action)
        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Role', 'Catatan', 'Tanggal']);
            foreach (\App\Models\Intervensi::orderBy('created_at', 'desc')->get() as $log) {
                fputcsv($file, [$log->id, $log->role, $log->catatan, $log->created_at]);
            }
            fclose($file);
        }, 'Audit_Trail.csv');
    }

    public function syncFromSiapKerja()
    {
        // FIX #2: Guard backend — Kepala Balai tidak boleh sinkronisasi data
        $this->assertWriteAccess();

        \App\Jobs\SyncSiapKerjaJob::dispatch();

        session()->flash('message', 'Perintah sinkronisasi API massal telah dikirim ke latar belakang (Background Job). Data akan diperbarui secara bertahap.');
        return redirect()->route('dashboard');
    }
}
