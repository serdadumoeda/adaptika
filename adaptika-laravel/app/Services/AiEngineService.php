<?php

namespace App\Services;

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
            // Dapatkan prompt spesifik berdasarkan role konteks
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

        if (empty($apiKey)) {
            if ($roleKonteks === "Career Passport") {
                return [
                    "narasi_kekuatan" => "[SIMULASI BACKEND] API Key belum diatur. Ini adalah narasi simulasi untuk kekuatan peserta.",
                    "rekomendasi_ekosistem" => "1. [Simulasi Ekosistem A]\n2. [Simulasi Ekosistem B]"
                ];
            }
            return [
                "tingkat_risiko" => "SEDANG",
                "analisis" => "[SIMULASI BACKEND] API Key belum diatur di .env (GROQ_API_KEY).",
                "rekomendasi_aksi" => "Harap konfigurasi API Key untuk respons AI asli."
            ];
        }

        $historyContext = "";
        if (!empty($riwayatIntervensi)) {
            $historyJson = json_encode($riwayatIntervensi, JSON_PRETTY_PRINT);
            $historyContext = "\n\n--- KONTEKS RIWAYAT (RAG) ---\nPerhatikan bahwa peserta ini sebelumnya telah mendapatkan penanganan:\n{$historyJson}\nBerikan analisis progresif lanjutan berdasarkan riwayat di atas, jangan mengulang saran yang sudah dilakukan.";
        }

        $userContent = $promptSpesifik . $historyContext;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
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
                return json_decode($content, true);
            }

            Log::error('Groq API Error', ['response' => $response->body()]);
            if ($roleKonteks === "Career Passport") {
                return [
                    "narasi_kekuatan" => "Gagal terhubung server backend (Status: {$response->status()}).",
                    "rekomendasi_ekosistem" => "Terjadi kesalahan pada integrasi Groq."
                ];
            }
            return [
                "tingkat_risiko" => "ERROR",
                "analisis" => "Gagal terhubung server backend (Status: {$response->status()}).",
                "rekomendasi_aksi" => "Terjadi kesalahan pada integrasi Groq."
            ];

        } catch (\Exception $e) {
            Log::error('Groq API Exception: ' . $e->getMessage());
            if ($roleKonteks === "Career Passport") {
                return [
                    "narasi_kekuatan" => "Error: " . $e->getMessage(),
                    "rekomendasi_ekosistem" => "Gagal memproses permintaan AI."
                ];
            }
            return [
                "tingkat_risiko" => "ERROR",
                "analisis" => $e->getMessage(),
                "rekomendasi_aksi" => "Gagal memproses permintaan AI."
            ];
        }
    }
}
