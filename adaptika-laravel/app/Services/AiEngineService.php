<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEngineService
{
    public function callAiGuardrailed(string $promptSpesifik, string $roleKonteks, array $riwayatIntervensi = null): array
    {
        $apiKey = config('services.groq.api_key');

        if ($roleKonteks === "Career Passport") {
            $systemPrompt = \App\Models\Setting::get('prompt_passport', <<<PROMPT
Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
Konteks: Penyusunan Suplemen Kompetensi Talenta (ADAPTIKA Career Passport).
- Gunakan bahasa pendampingan yang memberdayakan, profesional, dan berfokus pada "Growth Mindset".
- DILARANG KERAS menggunakan istilah teknis seperti 'Kuadran', 'Krisis Ganda', 'Mismatch', atau menyebut teori secara langsung.
- Fokus pada penjabaran potensi kekuatan adaptabilitas karier peserta berdasarkan profil RIASEC-nya.

Format JSON:
{
  "narasi_kekuatan": "Narasi 2-3 kalimat yang positif dan profesional tentang kekuatan adaptabilitas karier mereka. Jadikan paragraf ini bernilai jual (HR-friendly).",
  "rekomendasi_ekosistem": "1. [Ekosistem Industri A]\\n2. [Ekosistem Industri B]"
}
PROMPT
            );
        } else {
            $promptKey = 'prompt_instruktur';
            if ($roleKonteks === 'Pengantar Kerja') {
                $promptKey = 'prompt_pengantar';
            } elseif ($roleKonteks === 'Seksi Pemberdayaan') {
                $promptKey = 'prompt_pemberdayaan';
            }

            $systemPrompt = \App\Models\Setting::get($promptKey, <<<PROMPT
Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
Konteks RAG (Konseling Vokasional & Pedagogi): 
- JANGAN deterministik. Ketidaksesuaian minat (Mismatch RIASEC) BUKAN berarti pasti gagal. 
- Pertimbangkan "Faktor Protektif" peserta seperti motivasi ekonomi, transisi karier, dan growth mindset (Career Adaptability).
- Otoritas {$roleKonteks} terbatas pada bidangnya.

Format JSON:
{
  "tingkat_risiko": "TINGGI/SEDANG/RENDAH",
  "analisis": "Maksimal 2 kalimat fakta analisis keselarasan minat dan kendala belajar. Jangan memvonis gagal.",
  "rekomendasi_aksi": "PENTING: Output HARUS berupa TEKS STRING TUNGGAL (bukan array/dictionary). Jika role Instruktur: berikan langkah teknis matrikulasi. Jika role Konselor/Pengantar Kerja: Berikan 2 rumusan PERTANYAAN EKSPLORASI (Coaching Questions) dalam format teks bernomor '1. [Pertanyaan pertama]\\n2. [Pertanyaan kedua]'."
}
PROMPT
            );
        }

        $cacheKey = 'ai_rec_' . md5($promptSpesifik . $roleKonteks . json_encode($riwayatIntervensi));

        return Cache::remember($cacheKey, 604800, function () use ($promptSpesifik, $roleKonteks, $riwayatIntervensi, $systemPrompt, $apiKey) {
            if (empty($apiKey)) {
                return $this->generateHeuristicFallback($promptSpesifik, $roleKonteks);
            }

            $historyContext = "";
            if (!empty($riwayatIntervensi)) {
                $historyJson = json_encode($riwayatIntervensi, JSON_PRETTY_PRINT);
                $historyContext = "\n\n--- KONTEKS RIWAYAT (RAG) ---\nPerhatikan bahwa peserta ini sebelumnya telah mendapatkan penanganan:\n{$historyJson}\nBerikan analisis progresif lanjutan berdasarkan riwayat di atas, jangan mengulang saran yang sudah dilakukan.";
            }

            $userContent = $promptSpesifik . $historyContext;

            try {
                $response = Http::withToken($apiKey)
                    ->timeout(15)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => 'llama-3.1-8b-instant',
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userContent]
                        ],
                        'temperature' => 0.0,
                        'response_format' => ['type' => 'json_object']
                    ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }

                Log::warning('Groq API Non-Successful', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->generateHeuristicFallback($promptSpesifik, $roleKonteks);

            } catch (\Throwable $e) {
                Log::error('Groq API Exception: ' . $e->getMessage());
                return $this->generateHeuristicFallback($promptSpesifik, $roleKonteks);
            }
        });
    }

    /**
     * Fallback Heuristik berbasis aturan vokasional jika API Key tidak ada atau terjadi outage.
     */
    public function generateHeuristicFallback(string $promptSpesifik, string $roleKonteks): array
    {
        if ($roleKonteks === "Career Passport") {
            return [
                "narasi_kekuatan" => "Peserta memiliki adaptabilitas karier yang solid dengan perpaduan keahlian praktis dan etos kerja yang berorientasi pada hasil. Karakteristik ini memungkinkannya belajar dengan cepat di lingkungan bengkel vokasi serta siap beradaptasi dengan standar operasional industri.",
                "rekomendasi_ekosistem" => "1. Industri Manufaktur & Perakitan Modern\n2. Industri Jasa Vokasional & Wirausaha Terapan"
            ];
        }

        if ($roleKonteks === 'Pengantar Kerja') {
            return [
                "tingkat_risiko" => "SEDANG",
                "analisis" => "Peserta memerlukan bimbingan orientasi karier untuk menyelaraskan harapan pribadi dengan iklim kerja industri target.",
                "rekomendasi_aksi" => "1. Apakah bidang pekerjaan ini sesuai dengan minat jangka panjang Anda?\n2. Langkah persiapan apa yang menurut Anda paling menantang dalam transisi kerja ini?"
            ];
        }

        if ($roleKonteks === 'Seksi Pemberdayaan') {
            return [
                "tingkat_risiko" => "RENDAH",
                "analisis" => "Profil peserta menunjukkan kesiapan dasar yang baik untuk disalurkan ke program pemagangan industri atau inkubasi usaha.",
                "rekomendasi_aksi" => "1. Rekomendasikan ke mitra industri manufaktur/jasa lokal.\n2. Berikan pendampingan wirausaha mandiri jika minat eksplorasi tinggi."
            ];
        }

        // Default: Instruktur Teknis
        return [
            "tingkat_risiko" => "SEDANG",
            "analisis" => "Peserta menunjukkan potensi keterampilan dasar, namun memerlukan penguatan metode belajar praktis bertahap di bengkel.",
            "rekomendasi_aksi" => "1. Berikan demonstrasi ulang pengerjaan modul secara visual.\n2. Pasangkan dengan rakan sebaya (buddy system) yang lebih mumpuni."
        ];
    }
}
