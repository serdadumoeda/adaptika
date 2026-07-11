<?php

namespace App\Services;

/**
 * DataParserService
 *
 * Bertanggung jawab untuk mem-parse raw data JSON dari Skillhub/SiapLatih
 * menjadi format array yang siap disimpan ke tabel `pesertas`.
 *
 * CATATAN ARSITEKTUR:
 * Penentuan diagnosis kuadran (K1–K4) dilakukan sepenuhnya oleh:
 *   \App\Models\Peserta::calculateDiagnosis($num, $fig, $kejuruan, $riasecCode)
 * yang merupakan SATU-SATUNYA sumber kebenaran logika kuadran.
 * JANGAN duplikasi logika kuadran di sini.
 */
class DataParserService
{
    /**
     * Parse raw data from Skillhub/SiapLatih JSON format.
     * Memanggil Peserta::calculateDiagnosis() untuk menentukan kuadran per peserta.
     */
    public function parseSkillhubData(array $rawData): array
    {
        $parsedData = [];

        foreach ($rawData as $idx => $row) {
            $nama = $row['nama_peserta'] ?? 'Peserta-' . $idx;
            $kejuruan = $row['judul_pelatihan'] ?? 'Tidak Diketahui';

            $numScore = 0;
            $figScore = 0;

            if (!empty($row['detail_jawaban_siaplatih'])) {
                try {
                    $siaplatihData = json_decode($row['detail_jawaban_siaplatih'], true);
                    $testResults = $siaplatihData['detail']['test_result'] ?? [];
                    foreach ($testResults as $test) {
                        $testName = $test['name'] ?? '';
                        if (str_contains($testName, 'Numerik')) {
                            $numScore = $test['value'] ?? 0;
                        }
                        if (str_contains($testName, 'Figural')) {
                            $figScore = $test['value'] ?? 0;
                        }
                    }
                } catch (\Exception $e) {
                    // silent ignore
                }
            }

            $riasecCode = "";
            $riasecDesc = "";

            if (!empty($row['detail_jawaban_spi'])) {
                try {
                    $spiData = json_decode($row['detail_jawaban_spi'], true);
                    $hasil = $spiData['hasil_asesmen'][0] ?? [];
                    $riasecCode = $hasil['riasec'] ?? '';
                    $desc = $hasil['deskripsi'] ?? '';

                    if (preg_match('/kepribadian\s+([A-Za-z]+-[A-Za-z]+)/i', $desc, $matches)) {
                        $riasecDesc = $matches[1];
                    }
                } catch (\Exception $e) {
                    // silent ignore
                }
            }

            $diagnosisAwal = \App\Models\Peserta::calculateDiagnosis($numScore, $figScore, $kejuruan, $riasecCode);

            $parsedData[] = [
                'nama' => $nama,
                'kejuruan' => $kejuruan,
                'skor_logika_numerik' => $numScore,
                'skor_spasial_figural' => $figScore,
                'kode_riasec' => $riasecCode,
                'profil_riasec' => $riasecDesc,
                'detail_siaplatih' => $row['detail_jawaban_siaplatih'] ?? '{}',
                'diagnosis_awal' => $diagnosisAwal,
                'status_kelulusan' => 'Belum Dievaluasi',
                'status_instruktur' => 'Belum Ditangani',
                'catatan_instruktur' => null,
                'status_pengantar_kerja' => 'Belum Ditangani',
                'catatan_pengantar_kerja' => null,
                'status_pemberdayaan' => 'Belum Disalurkan',
                'catatan_pemberdayaan' => null
            ];
        }

        return $parsedData;
    }
}
