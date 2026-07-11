<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk integrasi dengan API SIAP Kerja (Kemnaker).
 * 
 * API SIAP Kerja bersifat tertutup (memerlukan NDA dari PUSDATIK Kemnaker).
 * Service ini dibangun dengan arsitektur siap-integrasi:
 * - Saat ini menggunakan data SIMULASI (mock)
 * - Ketika akses API resmi diperoleh, cukup ganti logic di method-method ini
 */
class SiapKerjaApiService
{
    private string $baseUrl;
    private ?string $apiKey;

    public function __construct()
    {
        // Membaca konfigurasi dari database settings dengan fallback ke config/env
        $this->baseUrl = \App\Models\Setting::get('siapkerja_api_url', config('services.siapkerja.base_url', 'https://api.siapkerja.kemnaker.go.id'));
        $this->apiKey = \App\Models\Setting::get('siapkerja_api_key', config('services.siapkerja.api_key'));
    }

    /**
     * Cek apakah integrasi sudah dikonfigurasi (API key tersedia).
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Ambil daftar program pelatihan dari SIAP Kerja.
     * 
     * @return array Format: [['kejuruan' => '...', 'program' => '...', 'kode_program' => '...'], ...]
     */
    public function getProgramPelatihan(): array
    {
        if (!$this->isConfigured()) {
            return $this->mockProgramPelatihan();
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/v1/pelatihan/programs");

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::error('SIAPKerja API: getProgramPelatihan failed', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('SIAPKerja API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil daftar peserta pelatihan berdasarkan kode program.
     * 
     * @return array Format: [['nama' => '...', 'kejuruan' => '...', 'program_pelatihan' => '...'], ...]
     */
    public function getPesertaPelatihan(?string $kodeProgram = null): array
    {
        if (!$this->isConfigured()) {
            return $this->mockPesertaPelatihan();
        }

        try {
            $params = $kodeProgram ? ['kode_program' => $kodeProgram] : [];
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/v1/pelatihan/peserta", $params);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::error('SIAPKerja API: getPesertaPelatihan failed', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('SIAPKerja API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil hasil asesmen SPI (RIASEC) peserta.
     * 
     * @return array Format: [['peserta_nama' => '...', 'kode_riasec' => '...', 'profil_riasec' => '...'], ...]
     */
    public function getHasilAsesmenSPI(?string $kodeProgram = null): array
    {
        if (!$this->isConfigured()) {
            return $this->mockHasilSPI();
        }

        try {
            $params = $kodeProgram ? ['kode_program' => $kodeProgram] : [];
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/v1/asesmen/spi", $params);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::error('SIAPKerja API: getHasilSPI failed', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('SIAPKerja API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil hasil asesmen SIAP Latih (Kognitif: Numerik & Figural).
     * 
     * @return array Format: [['peserta_nama' => '...', 'skor_numerik' => 75, 'skor_figural' => 60], ...]
     */
    public function getHasilSiapLatih(?string $kodeProgram = null): array
    {
        if (!$this->isConfigured()) {
            return $this->mockHasilSiapLatih();
        }

        try {
            $params = $kodeProgram ? ['kode_program' => $kodeProgram] : [];
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/v1/asesmen/siap-latih", $params);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::error('SIAPKerja API: getHasilSiapLatih failed', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('SIAPKerja API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sinkronisasi komprehensif: Gabungkan data peserta + SPI + SIAP Latih menjadi
     * satu dataset siap-insert ke tabel pesertas.
     * 
     * @return array ['inserted' => int, 'skipped' => int, 'errors' => string[]]
     */
    public function syncAll(): array
    {
        // 1. Sinkronisasi Kejuruan & Program dari SIAP Kerja dahulu
        $programList = $this->getProgramPelatihan();
        foreach ($programList as $p) {
            $kejuruanNama = $p['kejuruan'] ?? null;
            $programNama = $p['program'] ?? null;
            $kodeProgram = $p['kode_program'] ?? null;

            if ($kejuruanNama && $programNama) {
                $kj = \App\Models\Kejuruan::firstOrCreate(['nama' => $kejuruanNama]);
                \App\Models\Program::updateOrCreate(
                    ['kode_program' => $kodeProgram ?: null, 'nama' => $programNama],
                    ['kejuruan_id' => $kj->id]
                );
            }
        }

        // 2. Sinkronisasi Peserta Pelatihan
        $pesertaList = $this->getPesertaPelatihan();
        $spiData = collect($this->getHasilAsesmenSPI())->keyBy('peserta_nama');
        $siapLatihData = collect($this->getHasilSiapLatih())->keyBy('peserta_nama');

        $inserted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($pesertaList as $p) {
            $nama = $p['nama'] ?? null;
            if (!$nama) {
                $errors[] = "Data peserta tanpa nama dilewati.";
                continue;
            }

            // Cek duplikat
            $exists = \App\Models\Peserta::where('nama', $nama)
                ->where('kejuruan', $p['kejuruan'] ?? '')
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $spi = $spiData->get($nama, []);
            $siapLatih = $siapLatihData->get($nama, []);

            $num = (int)($siapLatih['skor_numerik'] ?? 0);
            $fig = (int)($siapLatih['skor_figural'] ?? 0);

            // Kalkulasi kuadran secara dinamis
            $diagnosis = \App\Models\Peserta::calculateDiagnosis($num, $fig, $p['kejuruan'] ?? '', $spi['kode_riasec'] ?? '');

            try {
                \App\Models\Peserta::create([
                    'nama' => $nama,
                    'kejuruan' => $p['kejuruan'] ?? '',
                    'program_pelatihan' => $p['program_pelatihan'] ?? null,
                    'skor_logika_numerik' => $num,
                    'skor_spasial_figural' => $fig,
                    'kode_riasec' => $spi['kode_riasec'] ?? null,
                    'profil_riasec' => $spi['profil_riasec'] ?? null,
                    'diagnosis_awal' => $diagnosis,
                ]);
                $inserted++;
            } catch (\Exception $e) {
                $errors[] = "Gagal menyimpan {$nama}: " . $e->getMessage();
            }
        }

        return compact('inserted', 'skipped', 'errors');
    }

    // ===== DATA SIMULASI (MOCK) =====
    // Akan diganti dengan data real saat API key dari PUSDATIK diperoleh

    private function mockProgramPelatihan(): array
    {
        return [
            ['kejuruan' => 'Teknik Las', 'program' => 'Juru Las SMAW', 'kode_program' => 'TL-SMAW-01'],
            ['kejuruan' => 'Teknik Las', 'program' => 'Juru Las GMAW/MIG', 'kode_program' => 'TL-GMAW-01'],
            ['kejuruan' => 'Bangunan', 'program' => 'Juru Gambar Arsitektur', 'kode_program' => 'BG-JGA-01'],
            ['kejuruan' => 'Bangunan', 'program' => 'Tukang Kayu', 'kode_program' => 'BG-TKY-01'],
            ['kejuruan' => 'Otomotif', 'program' => 'Teknisi Kendaraan Ringan', 'kode_program' => 'OT-TKR-01'],
            ['kejuruan' => 'TIK', 'program' => 'Web Programming', 'kode_program' => 'TI-WEB-01'],
        ];
    }

    private function mockPesertaPelatihan(): array
    {
        $names = ['Rina Handayani', 'Bimo Prasetyo', 'Laras Setiawati', 'Dimas Kurniawan', 'Sinta Maharani'];
        $programs = $this->mockProgramPelatihan();
        $result = [];

        foreach ($names as $name) {
            $prog = $programs[array_rand($programs)];
            $result[] = [
                'nama' => $name,
                'kejuruan' => $prog['kejuruan'],
                'program_pelatihan' => $prog['program'],
            ];
        }

        return $result;
    }

    private function mockHasilSPI(): array
    {
        $riasec = [
            'RIA' => 'Realistic-Investigative-Artistic',
            'SEC' => 'Social-Enterprising-Conventional',
            'ISA' => 'Investigative-Social-Artistic',
            'EAS' => 'Enterprising-Artistic-Social',
            'RSE' => 'Realistic-Social-Enterprising',
        ];
        $codes = array_keys($riasec);
        $names = ['Rina Handayani', 'Bimo Prasetyo', 'Laras Setiawati', 'Dimas Kurniawan', 'Sinta Maharani'];
        $result = [];

        foreach ($names as $name) {
            $code = $codes[array_rand($codes)];
            $result[] = [
                'peserta_nama' => $name,
                'kode_riasec' => $code,
                'profil_riasec' => $riasec[$code],
            ];
        }

        return $result;
    }

    private function mockHasilSiapLatih(): array
    {
        $names = ['Rina Handayani', 'Bimo Prasetyo', 'Laras Setiawati', 'Dimas Kurniawan', 'Sinta Maharani'];
        $result = [];

        foreach ($names as $name) {
            $result[] = [
                'peserta_nama' => $name,
                'skor_numerik' => rand(35, 95),
                'skor_figural' => rand(35, 95),
            ];
        }

        return $result;
    }
}
