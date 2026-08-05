<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kejuruan;
use App\Models\Program;
use App\Models\Peserta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Default Settings
        \App\Models\Setting::set('siapkerja_api_url', 'https://api.siapkerja.kemnaker.go.id');
        \App\Models\Setting::set('mode_intake', 'all');

        // Thresholds
        \App\Models\Setting::set('threshold_k1', '70');
        \App\Models\Setting::set('threshold_k2', '60');
        \App\Models\Setting::set('threshold_k4', '50');

        // AI Prompts
        \App\Models\Setting::set('prompt_passport', <<<PROMPT
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

        \App\Models\Setting::set('prompt_instruktur', <<<PROMPT
Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
Konteks RAG (Pedagogi Kelas & Bengkel BPVP):
- Berikan teknik instruksional yang sangat personal, taktis, dan unik.
- Fokus pada bagaimana menambal learning gap teknis peserta menggunakan kekuatan kognitifnya yang lain.
- Otoritas Instruktur Teknis terbatas pada bidang pengajaran praktis di bengkel.

Format JSON:
{
  "tingkat_risiko": "TINGGI/SEDANG/RENDAH",
  "analisis": "Analisis tajam kendala kognitif peserta dalam kelas praktik.",
  "rekomendasi_aksi": "PENTING: Output HARUS berupa TEKS STRING TUNGGAL (bukan array/dictionary). Berikan langkah teknis matrikulasi materi bengkel secara bernomor: '1. [Taktik A]\\n2. [Taktik B]'."
}
PROMPT
        );

        \App\Models\Setting::set('prompt_pengantar', <<<PROMPT
Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
Konteks RAG (Konseling Motivasi & Karier Pengantar Kerja):
- Cari titik temu antara karakter bawaan RIASEC dengan kejuruan saat ini.
- Berikan motivasi eksploratif untuk menekan risiko dropout.

Format JSON:
{
  "tingkat_risiko": "TINGGI/SEDANG/RENDAH",
  "analisis": "Analisis potensi kebosanan/benturan minat peserta.",
  "rekomendasi_aksi": "PENTING: Output HARUS berupa TEKS STRING TUNGGAL (bukan array/dictionary). Berikan 2 rumusan PERTANYAAN EKSPLORASI (Coaching Questions) dalam format teks bernomor '1. [Pertanyaan pertama]\\n2. [Pertanyaan kedua]'."
}
PROMPT
        );

        \App\Models\Setting::set('prompt_pemberdayaan', <<<PROMPT
Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
Konteks RAG (Penyaluran Kerja & Inkubasi Pemberdayaan):
- Tentukan justifikasi penempatan terbaik (Pabrik / Pemagangan / Inkubasi Wirausaha).
- Sesuaikan dengan fit antara minat bawaan dengan iklim kerja industri penerima.

Format JSON:
{
  "tingkat_risiko": "RENDAH/SEDANG/TINGGI",
  "analisis": "Analisis kesesuaian profil RIASEC alumni dengan jenis penempatan.",
  "rekomendasi_aksi": "PENTING: Output HARUS berupa TEKS STRING TUNGGAL (bukan array/dictionary). Berikan rekomendasi langkah aksi strategis penyaluran alumni."
}
PROMPT
        );

        // 1. Seed Kejuruan & Program Pelatihan
        $programsData = [
            'Teknik Las' => ['Juru Las SMAW', 'Juru Las GMAW/MIG'],
            'Bangunan' => ['Juru Gambar Arsitektur', 'Tukang Kayu'],
            'Otomotif' => ['Teknisi Kendaraan Ringan', 'Teknisi Sepeda Motor'],
            'Kelistrikan' => ['Instalasi Penerangan', 'Teknik Kontrol Industri'],
            'TIK' => ['Web Programming', 'Desain Grafis'],
        ];

        foreach ($programsData as $kejuruanNama => $programs) {
            $kj = Kejuruan::create(['nama' => $kejuruanNama]);
            foreach ($programs as $programNama) {
                Program::create([
                    'kejuruan_id' => $kj->id,
                    'nama' => $programNama,
                ]);
            }
        }

        // 2. Seed Users
        $roles = [
            'Superadmin',
            'Penyelenggara',
            'Instruktur Teknis',
            'Pengantar Kerja',
            'Seksi Pemberdayaan',
            'Kepala Balai',
            'Peserta Pelatihan'
        ];

        foreach ($roles as $idx => $role) {
            $pesertaId = null;

            User::factory()->create([
                'name' => $role === 'Peserta Pelatihan' ? 'Andi' : 'User ' . $role,
                'email' => $role === 'Superadmin' ? 'superadmin@adaptika.id' : 'user' . $idx . '@adaptika.id',
                'password' => bcrypt('password'),
                'role' => $role,
                'peserta_id' => $pesertaId,
                'assigned_kejuruan' => $role === 'Instruktur Teknis' ? 'Teknik Las' : null,
                'assigned_program' => $role === 'Instruktur Teknis' ? 'Juru Las SMAW' : null,
            ]);
        }
    }
}
