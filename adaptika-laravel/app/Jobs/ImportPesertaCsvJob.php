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

                $inserted++;
            }
        }

        Log::info("ImportPesertaCsvJob: Import Selesai. $inserted ditambahkan, $skipped dilewati.");
        
        // Hapus file temporary setelah diproses
        unlink($this->filePath);
    }
}
