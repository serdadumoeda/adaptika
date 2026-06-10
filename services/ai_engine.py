import json
import streamlit as st
from config.settings import get_groq_client

def call_ai_guardrailed(prompt_spesifik, role_konteks, riwayat_intervensi=None):
    client = get_groq_client()
    
    if role_konteks == "Career Passport":
        system_prompt = f"""
        Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
        Konteks: Penyusunan Suplemen Kompetensi Talenta (ADAPTIKA Career Passport).
        - Gunakan bahasa pendampingan yang memberdayakan, profesional, dan berfokus pada "Growth Mindset".
        - DILARANG KERAS menggunakan istilah teknis seperti 'Kuadran', 'Krisis Ganda', 'Mismatch', atau menyebut teori secara langsung.
        - Fokus pada penjabaran potensi kekuatan adaptabilitas karier peserta berdasarkan profil RIASEC-nya.
        
        Format JSON:
        {{
          "narasi_kekuatan": "Narasi 2-3 kalimat yang positif dan profesional tentang kekuatan adaptabilitas karier mereka. Jadikan paragraf ini bernilai jual (HR-friendly).",
          "rekomendasi_ekosistem": "1. [Ekosistem Industri A]\\n2. [Ekosistem Industri B]"
        }}
        """
    else:
        system_prompt = f"""
        Anda adalah ADAPTIKA API. Berikan respons dalam format JSON murni.
        Konteks RAG (Konseling Vokasional & Pedagogi): 
        - JANGAN deterministik. Ketidaksesuaian minat (Mismatch RIASEC) BUKAN berarti pasti gagal. 
        - Pertimbangkan "Faktor Protektif" peserta seperti motivasi ekonomi, transisi karier, dan growth mindset (Career Adaptability).
        - Otoritas {role_konteks} terbatas pada bidangnya.
        
        Format JSON:
        {{
          "tingkat_risiko": "TINGGI/SEDANG/RENDAH",
          "analisis": "Maksimal 2 kalimat fakta analisis keselarasan minat dan kendala belajar. Jangan memvonis gagal.",
          "rekomendasi_aksi": "PENTING: Output HARUS berupa TEKS STRING TUNGGAL (bukan array/dictionary). Jika role Instruktur: berikan langkah teknis matrikulasi. Jika role Konselor/Pengantar Kerja: Berikan 2 rumusan PERTANYAAN EKSPLORASI (Coaching Questions) dalam format teks bernomor '1. [Pertanyaan pertama]\\n2. [Pertanyaan kedua]'."
        }}
        """
        
    if client is None:
        return {
            "tingkat_risiko": "SEDANG",
            "analisis": "[SIMULASI BACKEND] API Key belum diatur di secrets.toml.",
            "rekomendasi_aksi": "Harap konfigurasi API Key untuk respons AI asli."
        }
        
    # Inject Smart History RAG jika riwayat_intervensi tidak kosong
    history_context = ""
    if riwayat_intervensi:
        history_context = f"\n\n--- KONTEKS RIWAYAT (RAG) ---\nPerhatikan bahwa peserta ini sebelumnya telah mendapatkan penanganan:\n{json.dumps(riwayat_intervensi, indent=2)}\nBerikan analisis progresif lanjutan berdasarkan riwayat di atas, jangan mengulang saran yang sudah dilakukan."
    
    user_content = prompt_spesifik + history_context
        
    try:
        response = client.chat.completions.create(
            model="llama-3.1-8b-instant",
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_content}
            ],
            temperature=0.0,
            response_format={ "type": "json_object" }
        )
        return json.loads(response.choices[0].message.content)
    except Exception as e:
        return {"tingkat_risiko": "ERROR", "analisis": str(e), "rekomendasi_aksi": "Gagal terhubung server backend."}

def display_ai_result(hasil_ai):
    if isinstance(hasil_ai, dict):
        risiko = str(hasil_ai.get("tingkat_risiko", "TIDAK DIKETAHUI")).upper()
        analisis = hasil_ai.get("analisis", "Tidak ada analisis")
        rekomendasi = str(hasil_ai.get("rekomendasi_aksi", "Tidak ada rekomendasi")).replace('\n', '<br>')
        
        # Dinamis: Jika mengandung tanda tanya, asumsikan ini adalah pertanyaan coaching/eksploratif
        if "?" in rekomendasi:
            action_label = "🎯 Pertanyaan Eksploratif (Coaching):"
        else:
            action_label = "🎯 Rekomendasi Aksi:"
        
        if "TINGGI" in risiko or "ERROR" in risiko:
            st.markdown(f'<div style="padding:1rem; border-radius:0.5rem; border: 1px solid rgba(255, 75, 75, 0.3); background-color:rgba(255, 75, 75, 0.1); color:#ff4b4b; margin-bottom:1rem;">🚨 <b>TINGKAT RISIKO: {risiko}</b><br><br><b>🧠 Analisis:</b><br>{analisis}<br><br><b>{action_label}</b><br>{rekomendasi}</div>', unsafe_allow_html=True)
        elif "SEDANG" in risiko:
            st.markdown(f'<div style="padding:1rem; border-radius:0.5rem; border: 1px solid rgba(255, 164, 33, 0.3); background-color:rgba(255, 164, 33, 0.1); color:#ffa421; margin-bottom:1rem;">⚠️ <b>TINGKAT RISIKO: {risiko}</b><br><br><b>🧠 Analisis:</b><br>{analisis}<br><br><b>{action_label}</b><br>{rekomendasi}</div>', unsafe_allow_html=True)
        else:
            st.markdown(f'<div style="padding:1rem; border-radius:0.5rem; border: 1px solid rgba(9, 171, 59, 0.3); background-color:rgba(9, 171, 59, 0.1); color:#09ab3b; margin-bottom:1rem;">✅ <b>TINGKAT RISIKO: {risiko}</b><br><br><b>🧠 Analisis:</b><br>{analisis}<br><br><b>{action_label}</b><br>{rekomendasi}</div>', unsafe_allow_html=True)
    else:
        st.error("Gagal membaca hasil AI (Format tidak valid).")
