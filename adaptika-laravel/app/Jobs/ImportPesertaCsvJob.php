<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportPesertaCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ImportPesertaCsvJob: Memulai proses import CSV dari ' . $this->filePath);

        if (!file_exists($this->filePath)) {
            Log::error('ImportPesertaCsvJob: File tidak ditemukan.');
            return;
        }

        $data = array_map('str_getcsv', file($this->filePath));
        $inserted = 0;
        $skipped  = 0;
        
        $countInstruktur = 0; // K2 & K4
        $countPengantar = 0; // K3 & K4

        foreach (array_slice($data, 1) as $row) {
            if (count($row) >= 7) {
                $nama      = trim($row[0]);
                $kejuruan  = trim($row[1]);
                $program   = trim($row[2]);
                $num       = (int) $row[3];
                $fig       = (int) $row[4];
                $riasecCode = trim($row[5]);
                $riasecDesc = trim($row[6] ?? '');

                $exists = \App\Models\Peserta::where('nama', $nama)
                    ->where('kejuruan', $kejuruan)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $diagnosis = \App\Models\Peserta::calculateDiagnosis($num, $fig, $kejuruan, $riasecCode);

                \App\Models\Peserta::create([
                    'nama'                => $nama,
                    'kejuruan'            => $kejuruan,
                    'program_pelatihan'   => $program,
                    'skor_logika_numerik' => $num,
                    'skor_spasial_figural' => $fig,
                    'kode_riasec'         => $riasecCode,
                    'profil_riasec'       => $riasecDesc,
                    'diagnosis_awal'      => $diagnosis,
                    'status_kelulusan'    => 'Belum Dievaluasi',
                    'status_instruktur'   => 'Belum Ditangani',
                    'status_pengantar_kerja' => 'Belum Ditangani',
                    'status_pemberdayaan' => 'Belum Disalurkan',
                ]);

                if (str_contains($diagnosis, 'Pendampingan') || str_contains($diagnosis, 'Perhatian')) {
                    $countInstruktur++;
                }
                if (str_contains($diagnosis, 'Eksplorasi') || str_contains($diagnosis, 'Perhatian')) {
                    $countPengantar++;
                }

                $inserted++;
            }
        }

        // Kirim Notifikasi
        if ($countInstruktur > 0) {
            $instrukturs = \App\Models\User::where('role', 'Instruktur Teknis')->get();
            \Illuminate\Support\Facades\Notification::send($instrukturs, new \App\Notifications\SistemNotification(
                'Tugas Mitigasi Baru',
                "Ada {$countInstruktur} peserta (K2/K4) baru hasil import CSV yang menunggu mitigasi Anda.",
                '/dashboard'
            ));
        }

        if ($countPengantar > 0) {
            $pengantars = \App\Models\User::where('role', 'Pengantar Kerja')->get();
            \Illuminate\Support\Facades\Notification::send($pengantars, new \App\Notifications\SistemNotification(
                'Tugas Konseling Baru',
                "Ada {$countPengantar} peserta (K3/K4) baru hasil import CSV yang menunggu konseling.",
                '/dashboard'
            ));
        }

        Log::info("ImportPesertaCsvJob: Import Selesai. $inserted ditambahkan, $skipped dilewati.");
        
        // Hapus file temporary setelah diproses
        unlink($this->filePath);
    }
}
