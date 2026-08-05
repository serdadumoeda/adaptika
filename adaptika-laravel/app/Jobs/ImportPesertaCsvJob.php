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

        if (empty($this->filePath) || !file_exists($this->filePath) || is_dir($this->filePath)) {
            Log::error('ImportPesertaCsvJob: File tidak valid atau berupa direktori: ' . $this->filePath);
            return;
        }

        $rawLines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($rawLines)) {
            Log::error('ImportPesertaCsvJob: File CSV kosong.');
            return;
        }

        // Auto-detect delimiter from first line
        $firstLine = $rawLines[0];
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $delim => $c) {
            $delimiters[$delim] = substr_count($firstLine, $delim);
        }
        arsort($delimiters);
        $delimiter = key($delimiters);
        if ($delimiters[$delimiter] === 0) {
            $delimiter = ',';
        }

        // Parse lines into array of rows using detected delimiter
        $rows = [];
        foreach ($rawLines as $line) {
            $cleanLine = preg_replace('/^[\xEF\xBB\xBF]/', '', trim($line));
            if (empty($cleanLine)) continue;
            $row = str_getcsv($cleanLine, $delimiter);
            if (count($row) >= 1) {
                $rows[] = array_map(fn($v) => trim($v, " \t\n\r\0\x0B\"'\xEF\xBB\xBF"), $row);
            }
        }

        if (empty($rows)) return;

        // Header detection
        $header = array_map('strtolower', $rows[0]);
        $hasHeader = false;
        $colIndex = [];
        
        foreach ($header as $idx => $colName) {
            if (str_contains($colName, 'nama')) { $hasHeader = true; $colIndex['nama'] = $idx; }
            if (str_contains($colName, 'kejuruan')) { $hasHeader = true; $colIndex['kejuruan'] = $idx; }
            if (str_contains($colName, 'program')) { $hasHeader = true; $colIndex['program'] = $idx; }
            if (str_contains($colName, 'numerik') || str_contains($colName, 'logika')) { $hasHeader = true; $colIndex['num'] = $idx; }
            if (str_contains($colName, 'figural') || str_contains($colName, 'spasial')) { $hasHeader = true; $colIndex['fig'] = $idx; }
            if (str_contains($colName, 'riasec') || str_contains($colName, 'kode')) { $hasHeader = true; $colIndex['riasec'] = $idx; }
            if (str_contains($colName, 'profil') || str_contains($colName, 'deskripsi')) { $hasHeader = true; $colIndex['profil'] = $idx; }
            if (str_contains($colName, 'angkatan') || str_contains($colName, 'batch')) { $hasHeader = true; $colIndex['angkatan'] = $idx; }
        }

        $inserted = 0;
        $skipped  = 0;
        $countInstruktur = 0; // K2 & K4
        $countPengantar = 0; // K3 & K4

        $startIndex = $hasHeader ? 1 : 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty($row) || (count($row) === 1 && empty($row[0]))) continue;

            $nama = '';
            $kejuruan = '';
            $program = '';
            $num = 0;
            $fig = 0;
            $riasecCode = '';
            $riasecDesc = '';
            $angkatan = 'Batch 1 - 2026';

            // Cek jika kolom mengandung JSON (Format SIAPkerja Raw Export)
            $isRawSiapkerja = false;
            foreach ($row as $col) {
                if (is_string($col) && (str_contains($col, 'detail_jawaban_siaplatih') || str_contains($col, 'test_result') || str_contains($col, 'hasil_asesmen'))) {
                    $isRawSiapkerja = true;
                    break;
                }
            }

            if ($isRawSiapkerja || (isset($row[6]) && str_contains($row[6], '{'))) {
                // Format SIAPkerja Raw Export (seperti test.csv)
                $nama = trim($row[1] ?? $row[0]);
                $kejuruan = trim($row[4] ?? $row[1]);
                $program = $kejuruan;
                
                if (isset($row[6]) && str_contains($row[6], '{')) {
                    $json = json_decode($row[6], true);
                    $testResults = $json['detail']['test_result'] ?? [];
                    foreach ($testResults as $t) {
                        $tName = $t['name'] ?? '';
                        if (str_contains($tName, 'Numerik')) $num = (int)($t['value'] ?? 0);
                        if (str_contains($tName, 'Figural')) $fig = (int)($t['value'] ?? 0);
                    }
                }
                
                if (isset($row[8]) && str_contains($row[8], '{')) {
                    $jsonSpi = json_decode($row[8], true);
                    $hasil = $jsonSpi['hasil_asesmen'][0] ?? [];
                    $riasecCode = $hasil['riasec'] ?? '';
                    $riasecDesc = $hasil['deskripsi'] ?? '';
                }
            } elseif ($hasHeader) {
                // Header-based mapping
                $nama = trim($row[$colIndex['nama'] ?? 0] ?? '');
                $kejuruan = trim($row[$colIndex['kejuruan'] ?? 1] ?? '');
                $program = trim($row[$colIndex['program'] ?? 2] ?? $kejuruan);
                $num = (int) ($row[$colIndex['num'] ?? 3] ?? 0);
                $fig = (int) ($row[$colIndex['fig'] ?? 4] ?? 0);
                $riasecCode = trim($row[$colIndex['riasec'] ?? 5] ?? '');
                $riasecDesc = trim($row[$colIndex['profil'] ?? 6] ?? '');
                $angkatan = trim($row[$colIndex['angkatan'] ?? 7] ?? 'Batch 1 - 2026');
            } else {
                // Format Sederhana / Positional mapping fallback
                $nama = trim($row[0] ?? '');
                $kejuruan = trim($row[1] ?? '');
                $program = trim($row[2] ?? $kejuruan);
                $num = (int) ($row[3] ?? 0);
                $fig = (int) ($row[4] ?? 0);
                $riasecCode = trim($row[5] ?? '');
                $riasecDesc = trim($row[6] ?? '');
                $angkatan = trim($row[7] ?? 'Batch 1 - 2026');
            }

            if (empty($nama) || empty($kejuruan)) continue;

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
                'angkatan'            => $angkatan,
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
        @unlink($this->filePath);
    }
}
