<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Peserta extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'nama',
        'kejuruan',
        'program_pelatihan',
        'angkatan',
        'skor_logika_numerik',
        'skor_spasial_figural',
        'kode_riasec',
        'profil_riasec',
        'detail_siaplatih',
        'diagnosis_awal',
        'status_kelulusan',
        'status_instruktur',
        'catatan_instruktur',
        'status_pengantar_kerja',
        'catatan_pengantar_kerja',
        'status_pemberdayaan',
        'catatan_pemberdayaan',
    ];

    public static function calculateDiagnosis($num, $fig, ?string $kejuruan = '', ?string $riasecCode = ''): string
    {
        $threshold = (int) Setting::get('threshold_k2', '60');
        
        $kj = strtolower($kejuruan ?? '');
        $riasec = strtoupper($riasecCode ?? '');
        
        $kogAman = true;
        $psiAman = true;

        // Aturan per kejuruan
        if (str_contains($kj, 'las') || str_contains($kj, 'listrik') || str_contains($kj, 'kelistrikan') || str_contains($kj, 'bangunan') || str_contains($kj, 'otomotif')) {
            if ($fig < $threshold) {
                $kogAman = false;
            }
            if (!str_contains($riasec, 'R')) {
                $psiAman = false;
            }
        } elseif (str_contains($kj, 'web') || str_contains($kj, 'tik') || str_contains($kj, 'programming') || str_contains($kj, 'desain') || str_contains($kj, 'grafis')) {
            if ($num < $threshold) {
                $kogAman = false;
            }
            if (!str_contains($riasec, 'I') && !str_contains($riasec, 'A')) {
                $psiAman = false;
            }
        } else {
            // Fallback default
            if ($num < $threshold && $fig < $threshold) {
                $kogAman = false;
            }
            // Untuk kejuruan tak dikenal, kriteria psikologis bernilai true secara default
            $psiAman = true;
        }

        if ($kogAman && $psiAman) {
            return 'Kuadran 1 (Kapasitas Mumpuni)';
        } elseif (!$kogAman && $psiAman) {
            return 'Kuadran 2 (Perlu Pendampingan)';
        } elseif ($kogAman && !$psiAman) {
            return 'Kuadran 3 (Sedang Dieksplorasi)';
        } else {
            return 'Kuadran 4 (Perlu Perhatian Khusus)';
        }
    }
}
