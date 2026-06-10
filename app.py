import streamlit as st
import pandas as pd
import json
import re
from openai import OpenAI
from datetime import datetime
import plotly.express as px 
from streamlit_gsheets import GSheetsConnection 

from config.settings import Settings
from services.ai_engine import call_ai_guardrailed, display_ai_result
from database.db_manager import db
from utils.pdf_generator import clean_pdf_text, generate_pdf_report, generate_career_passport

from ui.styles import inject_custom_css
from ui.components import render_login_gateway
from ui.visualizations import (
    create_progress_instruktur_chart,
    create_progress_pk_chart,
    create_trend_kejuruan_chart,
    create_scatter_kognitif_chart,
    create_radar_kognitif_chart
)

# ==========================================
# 1. KONFIGURASI ENTERPRISE & SESSION STATE
# ==========================================
st.set_page_config(page_title="ADAPTIKA", page_icon="🏢", layout="wide")

# Custom CSS for Modern, Responsive and Premium UI
inject_custom_css()

if 'logged_in' not in st.session_state:
    st.session_state.logged_in = False
if 'role' not in st.session_state:
    st.session_state.role = None
if 'df_peserta' not in st.session_state:
    st.session_state.df_peserta = pd.DataFrame()
if 'audit_trail' not in st.session_state:
    st.session_state.audit_trail = []
if 'groq_api_key' not in st.session_state:
    # Try to load from secrets first, fall back to empty string
    try:
        st.session_state.groq_api_key = st.secrets.get("API_KEY", "")
    except Exception:
        st.session_state.groq_api_key = ""

GOOGLE_SHEET_URL = "https://docs.google.com/spreadsheets/d/1BxKQenKeUVFV0XEUFBsW-r-cejYUzz76FR7-PI9Y3D0/edit"

# ==========================================
# 3. PARSER ENGINE API SKILLHUB (DATA INGESTION)
# ==========================================
def parse_skillhub_data(df_raw):
    parsed_data = []
    
    for idx, row in df_raw.iterrows():
        nama = row.get('nama_peserta', f'Peserta-{idx}')
        kejuruan = row.get('judul_pelatihan', 'Tidak Diketahui')
        
        num_score = 0
        fig_score = 0
        if pd.notna(row.get('detail_jawaban_siaplatih')):
            try:
                siaplatih_data = json.loads(row['detail_jawaban_siaplatih'])
                test_results = siaplatih_data.get('detail', {}).get('test_result', [])
                for test in test_results:
                    if 'Numerik' in test.get('name', ''): num_score = test.get('value', 0)
                    if 'Figural' in test.get('name', ''): fig_score = test.get('value', 0)
            except Exception: pass
                
        riasec_code = ""
        riasec_desc = ""
        if pd.notna(row.get('detail_jawaban_spi')):
            try:
                spi_data = json.loads(row['detail_jawaban_spi'])
                hasil = spi_data.get('hasil_asesmen', [{}])[0]
                riasec_code = hasil.get('riasec', '')
                desc = hasil.get('deskripsi', '')
                match = re.search(r'kepribadian\s+([A-Za-z]+-[A-Za-z]+)', desc)
                if match: riasec_desc = match.group(1)
            except Exception: pass
                
        parsed_data.append({
            'Nama': nama,
            'Kejuruan': kejuruan,
            'Skor_Logika_Numerik': num_score,
            'Skor_Spasial_Figural': fig_score,
            'Kode_RIASEC': riasec_code,
            'Profil_RIASEC': riasec_desc,
            'Detail_SiapLatih': row.get('detail_jawaban_siaplatih', '{}'), 
            'Status_Kelulusan': 'Belum Dievaluasi',
            'Status_Instruktur': 'Belum Ditangani',
            'Catatan_Instruktur': '-',
            'Status_Pengantar_Kerja': 'Belum Ditangani',
            'Catatan_Pengantar_Kerja': '-',
            'Status_Pemberdayaan': 'Belum Disalurkan',
            'Catatan_Pemberdayaan': '-'
        })
        
    df_parsed = pd.DataFrame(parsed_data)
    
    def tentukan_kuadran(row):
        kj = str(row['Kejuruan']).lower()
        kog_aman = True
        psi_aman = True
        
        if 'las' in kj or 'listrik' in kj:
            if row['Skor_Spasial_Figural'] < 60: kog_aman = False
        elif 'web' in kj or 'tik' in kj:
            if row['Skor_Logika_Numerik'] < 60: kog_aman = False
            
        if 'las' in kj or 'listrik' in kj:
            if 'R' not in row['Kode_RIASEC']: psi_aman = False
        elif 'web' in kj or 'tik' in kj:
            if 'I' not in row['Kode_RIASEC']: psi_aman = False
            
        if kog_aman and psi_aman: return "Kuadran 1 (Kapasitas Mumpuni)"
        elif not kog_aman and psi_aman: return "Kuadran 2 (Learning Gap Teknis)"
        elif kog_aman and not psi_aman: return "Kuadran 3 (Risiko Kelelahan/Fatigue)"
        else: return "Kuadran 4 (Krisis Ganda/Burnout)"

    if not df_parsed.empty:
        df_parsed['Diagnosis_Awal'] = df_parsed.apply(tentukan_kuadran, axis=1)
    return df_parsed

# ==========================================
# 4. AI GUARDRAILS TERSPESIALISASI PER ROLE
# (Di-handle secara eksternal melalui services/ai_engine.py)
# ==========================================

# ==========================================
# 2. SISTEM RBAC (LOGIN & GATEWAY)
# ==========================================
if not st.session_state.logged_in:
    render_login_gateway()
    st.stop()

# ==========================================
# 6. UI UTAMA & NAVIGASI
# ==========================================
df = st.session_state.df_peserta
role = st.session_state.role

if df.empty or 'Diagnosis_Awal' not in df.columns:
    st.session_state.clear()
    st.rerun()

if not df.empty:
    cols_to_remove = ['Progress_%', 'Status_Intervensi', 'Catatan_Intervensi', 'Status_Konselor', 'Catatan_Konselor']
    df = df.drop(columns=[c for c in cols_to_remove if c in df.columns])
    
    if 'Status_Kelulusan' not in df.columns: df['Status_Kelulusan'] = 'Belum Dievaluasi'
    if 'Status_Instruktur' not in df.columns: df['Status_Instruktur'] = 'Belum Ditangani'
    if 'Catatan_Instruktur' not in df.columns: df['Catatan_Instruktur'] = '-'
    if 'Status_Pengantar_Kerja' not in df.columns: df['Status_Pengantar_Kerja'] = 'Belum Ditangani'
    if 'Catatan_Pengantar_Kerja' not in df.columns: df['Catatan_Pengantar_Kerja'] = '-'
    if 'Status_Pemberdayaan' not in df.columns: df['Status_Pemberdayaan'] = 'Belum Disalurkan'
    if 'Catatan_Pemberdayaan' not in df.columns: df['Catatan_Pemberdayaan'] = '-'
    st.session_state.df_peserta = df

help_kuadran = """
**Penjelasan Analisis Kesiapan & Keselarasan Belajar:**
- **Kuadran 1 (Aman):** Kesiapan sesuai tingkat materi & Minat selaras. 
- **Kuadran 2 (Learning Gap Teknis):** Butuh intervensi pedagogi pengajaran dari Instruktur. 
- **Kuadran 3 (Risiko Fatigue):** Mismatch profil yang berpotensi memicu demotivasi. Butuh Pengantar Kerja. 
- **Kuadran 4 (Krisis Ganda):** Risiko drop-out tertinggi akibat akumulasi beban mental dan mismatch. 
"""

if role == "Instruktur Teknis":
    jumlah_tunggu = len(df[df['Diagnosis_Awal'].str.contains("Gap|Krisis", na=False) & (df['Status_Instruktur'] == 'Belum Ditangani')])
elif role == "Pengantar Kerja":
    jumlah_tunggu = len(df[df['Diagnosis_Awal'].str.contains("Risiko|Krisis", na=False) & (df['Status_Pengantar_Kerja'] == 'Belum Ditangani')])
elif role == "Seksi Pemberdayaan":
    jumlah_tunggu = len(df[(df['Status_Kelulusan'] == 'Kompeten') & (df['Status_Pemberdayaan'] == 'Belum Disalurkan')])
else:
    jumlah_tunggu = len(df[(df['Status_Instruktur'] == 'Belum Ditangani') | (df['Status_Pengantar_Kerja'] == 'Belum Ditangani')])

with st.sidebar:
    st.image("https://kemnaker.go.id/assets/images/logo.png", width=80)
    st.markdown("### **ADAPTIKA**")
    st.caption("Human-Centric & Psychological Analytics System")
    st.divider()
    
    st.info(f"👤 **Operator BPVP:** {role}")
    st.metric("Risiko Fatigue / Drop-out", len(df[df['Diagnosis_Awal'].str.contains("Risiko|Krisis", na=False)]))
    
    st.divider()
    if st.button("🚪 Logout (Hapus Memori)"):
        st.session_state.clear()
        st.rerun()

st.title(f"Dasbor {role}")

if role != "Peserta Pelatihan":
    col_m1, col_m2 = st.columns(2)
    with col_m1:
        st.metric("Total Peserta Pelatihan", len(df))
    with col_m2:
        st.metric("Menunggu Tindakan Anda", jumlah_tunggu)

st.divider()

# ==========================================
# DASBOR 1: PENYELENGGARA PELATIHAN
# ==========================================
if role == "Penyelenggara Pelatihan":
    tab1, tab2, tab3 = st.tabs(["📊 Analisis & Persebaran", "⚙️ Keputusan Manajerial", "📑 Rekapitulasi Data"])
    
    with tab1:
        st.markdown("### 📊 Indeks Kesiapan Kerja & Potensi Kendala Belajar")
        
        col1, col2, col3 = st.columns(3)
        with col1:
            df_diag = df['Diagnosis_Awal'].value_counts().reset_index()
            df_diag.columns = ['Status Kesiapan', 'Jumlah']
            fig_diag = px.bar(
                df_diag, 
                x='Jumlah', 
                y='Status Kesiapan', 
                orientation='h',
                color='Status Kesiapan',
                color_discrete_sequence=px.colors.qualitative.Pastel,
                title="Pemetaan Kesiapan Kelas"
            )
            fig_diag.update_layout(
                showlegend=False, 
                xaxis_title="", 
                yaxis_title="", 
                margin=dict(l=10, r=10, t=40, b=10),
                height=260,
                paper_bgcolor='rgba(0,0,0,0)',
                plot_bgcolor='rgba(0,0,0,0)',
                font_family="Plus Jakarta Sans"
            )
            fig_diag.update_xaxes(showgrid=True, gridwidth=1, gridcolor='#f1f5f9')
            st.plotly_chart(fig_diag, use_container_width=True)
            
        with col2:
            fig_inst = create_progress_instruktur_chart(df)
            st.plotly_chart(fig_inst, use_container_width=True)
            
        with col3:
            fig_pk = create_progress_pk_chart(df)
            st.plotly_chart(fig_pk, use_container_width=True)
            
        st.divider()
        
        st.markdown("### 📊 Distribusi Risiko Kesiapan Berdasarkan Kejuruan")
        st.caption("Analisis agregat untuk melihat kejuruan mana yang membutuhkan perhatian khusus dari manajemen.")
        fig_trend = create_trend_kejuruan_chart(df)
        st.plotly_chart(fig_trend, use_container_width=True)
        
        st.divider()
        
        st.markdown("### 📈 Peta Persebaran Kesiapan Pelatihan Teknis")
        st.caption("Visualisasi penyebaran potensi performa pelatihan untuk memprediksi ketimpangan serapan materi di dalam bengkel kerja.")
        fig_scatter = create_scatter_kognitif_chart(df)
        st.plotly_chart(fig_scatter, use_container_width=True)
        
    with tab2:
        st.markdown("### ⚙️ Manajemen Kelelahan & Penyesuaian Sarpras BPVP")
        st.info("Catat intervensi manajerial (contoh: Penambahan modul visual, penyesuaian durasi istirahat, dsb) untuk menjaga kesehatan mental peserta.")
        catatan_manajerial = st.text_area("Log Keputusan Manajerial BPVP:", placeholder="Ketik tindakan manajemen di sini...")
        if st.button("Simpan Keputusan Manajerial", type="primary"):
            st.session_state.audit_trail.append(f"[{datetime.now().strftime('%H:%M:%S')}] PENYELENGGARA: {catatan_manajerial}")
            st.success("Keputusan berhasil disimpan ke Log Sistem!")
            
    with tab3:
        st.markdown("### 📑 Rekapitulasi Historis Peserta (Feedback Loop)")
        st.write("Pantau pergerakan intervensi untuk audit internal BPVP:")
        
        st.dataframe(
            df[['Nama', 'Kejuruan', 'Diagnosis_Awal', 'Status_Instruktur', 'Catatan_Instruktur', 'Status_Pengantar_Kerja', 'Catatan_Pengantar_Kerja']], 
            use_container_width=True,
            hide_index=True,
            column_config={
                "Diagnosis_Awal": st.column_config.TextColumn("Status Kesiapan", help=help_kuadran),
            }
        )
        
        st.divider()
        col_dl1, col_dl2 = st.columns(2)
        with col_dl1:
            if 'csv_rekap_bytes' not in st.session_state:
                st.session_state.csv_rekap_bytes = df.to_csv(index=False).encode('utf-8')
            st.download_button(
                label="📥 Unduh Laporan Rekapitulasi BPVP (CSV)", 
                data=st.session_state.csv_rekap_bytes, 
                file_name="Laporan_Rekapitulasi_BPVP.csv", 
                mime="text/csv", 
                type="primary", 
                use_container_width=True
            )
        with col_dl2:
            if st.session_state.audit_trail:
                if 'csv_log_bytes' not in st.session_state:
                    log_df = pd.DataFrame({"Audit_Log": st.session_state.audit_trail})
                    st.session_state.csv_log_bytes = log_df.to_csv(index=False).encode('utf-8')
                st.download_button(
                    label="📥 Unduh Audit Trail Kemnaker (CSV)", 
                    data=st.session_state.csv_log_bytes, 
                    file_name="Audit_Trail.csv", 
                    mime="text/csv", 
                    type="primary", 
                    use_container_width=True
                )
            else:
                st.button("📥 Unduh Audit Trail Kemnaker (Kosong)", disabled=True, use_container_width=True)

# ==========================================
# DASBOR 2: INSTRUKTUR
# ==========================================
elif role == "Instruktur Teknis":
    st.markdown("### 🛠️ Dasbor Instruktur: Manajemen Potensi Kendala Belajar")
    st.caption("Fokus: Memodifikasi teknik pengajaran bengkel untuk peserta dengan indikasi learning gap teknis.")
    
    mask_masalah = df['Diagnosis_Awal'].str.contains("Gap|Krisis", na=False)
    mask_belum = df['Status_Instruktur'] == "Belum Ditangani"
    df_instruktur = df[mask_masalah & mask_belum]
    
    if not df_instruktur.empty:
        st.markdown("#### 📋 Daftar Antrean Peserta Butuh Pendampingan")
        st.dataframe(
            df_instruktur[['Nama', 'Kejuruan', 'Diagnosis_Awal']], 
            hide_index=True,
            use_container_width=True,
            column_config={"Diagnosis_Awal": st.column_config.TextColumn("Diagnosis Awal", help=help_kuadran)}
        )
        
        with st.container(border=True):
            st.markdown("#### 📝 Panel Tindakan Cepat")
            pilih_peserta = st.selectbox("Pilih Peserta untuk Penyesuaian Pedagogis:", df_instruktur['Nama'])
            dt = df_instruktur[df_instruktur['Nama'] == pilih_peserta].iloc[0]
            
            tab1, tab2 = st.tabs(["🧩 Pemetaan Kesiapan Pelatihan", "🤖 AI Rekomendasi"])
            
            with tab1:
                st.markdown(f"### 🕸️ Profil Kesiapan Pelatihan: {dt['Nama']}")
                
                scores_dict = {
                    "Pengetahuan Umum": 0,
                    "Kemampuan Verbal": 0,
                    "Logika Numerik": dt.get('Skor_Logika_Numerik', 0),
                    "Spasial Figural": dt.get('Skor_Spasial_Figural', 0)
                }
                
                try:
                    raw_siaplatih = str(dt.get('Detail_SiapLatih', '{}'))
                    if pd.notna(raw_siaplatih) and raw_siaplatih.strip() != "" and raw_siaplatih != "{}":
                        siaplatih_data = json.loads(raw_siaplatih)
                        test_results = siaplatih_data.get('detail', {}).get('test_result', [])
                        if test_results:
                            for test in test_results:
                                name = test.get('name', 'Kategori')
                                val = test.get('value', 0)
                                if 'Umum' in name: scores_dict["Pengetahuan Umum"] = val
                                elif 'Verbal' in name: scores_dict["Kemampuan Verbal"] = val
                                elif 'Numerik' in name: scores_dict["Logika Numerik"] = val
                                elif 'Figural' in name: scores_dict["Spasial Figural"] = val
                                else: scores_dict[name] = val
                except Exception: pass 
                
                df_radar = pd.DataFrame(dict(Nilai=list(scores_dict.values()), Kategori=list(scores_dict.keys())))
                if scores_dict:
                    kategori_tertinggi = max(scores_dict, key=scores_dict.get)
                    kategori_terendah = min(scores_dict, key=scores_dict.get)
                    nilai_terendah = scores_dict[kategori_terendah]
                    nilai_tertinggi = scores_dict[kategori_tertinggi]
                else:
                    kategori_tertinggi, kategori_terendah, nilai_terendah, nilai_tertinggi = "-", "-", 0, 0
    
                col_chart, col_text = st.columns([1, 1.2])
                with col_chart:
                    fig_radar = create_radar_kognitif_chart(df_radar)
                    st.plotly_chart(fig_radar, use_container_width=True, theme=None)
                    
                with col_text:
                    st.info(f"**💡 Kekuatan Kesiapan Pelatihan:**\nPeserta unggul di **{kategori_tertinggi}** ({nilai_tertinggi}/100). Pendekatan materi disarankan menggunakan kekuatan ini.")
                    st.error(f"**⚠️ Indikasi Learning Gap Teknis:**\nPeserta memiliki kendala pada **{kategori_terendah}** (Skor: {nilai_terendah}/100). Hal ini berisiko memperlambat daya tangkap di bengkel kerja jika instruktur memaksakan metode standar.")
                    skor_str = ", ".join([f"{k}: {v}" for k, v in scores_dict.items()])
                    
            with tab2:
                st.markdown(f"#### 💡 Mitigasi Kendala Belajar & Pedagogi: {dt['Nama']}")
                st.caption("Gunakan rekomendasi asisten AI untuk memformulasikan taktik matrikulasi bengkel.")
                
                ai_cache_key = f"ai_result_instruktur_{dt['Nama']}"
                if ai_cache_key not in st.session_state:
                    with st.spinner("Menganalisis skenario penurunan kendala belajar secara otomatis..."):
                        prompt = f"""
                        Peserta {dt['Nama']} mengikuti kelas praktik {dt['Kejuruan']}. 
                        Pemetaan kesiapan pelatihannya: {skor_str}. 
                        Ia sangat kesulitan di area {kategori_terendah} ({nilai_terendah}/100), NAMUN ia memiliki keunggulan kuat di {kategori_tertinggi} ({nilai_tertinggi}/100). 
                        Tugas: Berikan 1 teknik instruksional yang SANGAT PERSONAL dan UNIK. Bagaimana Instruktur dapat menggunakan kekuatan {kategori_tertinggi}-nya untuk membantu ia memahami materi yang membutuhkan {kategori_terendah}? JANGAN gunakan contoh klise/template standar.
                        """
                        
                        # Membangun Smart History (RAG JSON)
                        riwayat = {}
                        if str(dt.get('Catatan_Pengantar_Kerja', 'nan')) not in ['nan', '-', '']:
                            riwayat['Catatan_Pengantar_Kerja'] = dt['Catatan_Pengantar_Kerja']
                        if str(dt.get('Catatan_Pemberdayaan', 'nan')) not in ['nan', '-', '']:
                            riwayat['Catatan_Pemberdayaan'] = dt['Catatan_Pemberdayaan']
                            
                        st.session_state[ai_cache_key] = call_ai_guardrailed(prompt, "Instruktur Teknis", riwayat)
                
                display_ai_result(st.session_state[ai_cache_key])
                        
                catatan = st.text_area("Log Tindakan Instruktur (Penyesuaian Pedagogis):", placeholder="Misal: Modul disederhanakan, praktik dibagi menjadi langkah-langkah kecil (chunking)...")
                if st.button("Simpan Keputusan & Tandai Selesai", type="primary"):
                    st.session_state.audit_trail.append(f"[{datetime.now().strftime('%H:%M:%S')}] INSTRUKTUR: {pilih_peserta} -> {catatan}")
                    idx = df.index[df['Nama'] == dt['Nama']].tolist()[0]
                    
                    current_note = str(st.session_state.df_peserta.at[idx, 'Catatan_Instruktur'])
                    new_note = f"Instruktur: {catatan}"
                    if current_note == '-' or current_note.strip() == '' or current_note == 'nan': 
                        st.session_state.df_peserta.at[idx, 'Catatan_Instruktur'] = new_note
                    else: 
                        st.session_state.df_peserta.at[idx, 'Catatan_Instruktur'] = f"{current_note} | {new_note}"
                    
                    st.session_state.df_peserta.at[idx, 'Status_Instruktur'] = "Sudah Ditangani"
                    success = db.update_record(st.session_state.df_peserta)
                    if success:
                        st.toast(f"Berhasil! Tugas selesai. {pilih_peserta} telah ditandai.", icon="✅")
                    else:
                        st.toast("GAGAL menyimpan ke GSheets.", icon="⚠️")
                        
                    st.rerun() 
    else:
        st.success("🎉 Luar biasa! Seluruh potensi kendala belajar teknis peserta telah dimitigasi.")
        st.balloons()

# ==========================================
# DASBOR 3: PENGANTAR KERJA
# ==========================================
elif role == "Pengantar Kerja":
    st.markdown("### 🧠 Dasbor Pengantar Kerja: Peringatan Dini Kelelahan Mental")
    st.caption("Fokus: Memonitor peserta dengan benturan minat kerja yang berpotensi memicu kebosanan dan Drop-out dari BPVP.")
    
    mask_masalah = df['Diagnosis_Awal'].str.contains("Risiko|Krisis", na=False)
    mask_belum = df['Status_Pengantar_Kerja'] == "Belum Ditangani"
    df_pk = df[mask_masalah & mask_belum]
    
    if not df_pk.empty:
        st.markdown("#### 📋 Daftar Antrean Peserta Butuh Pendampingan")
        st.dataframe(
            df_pk[['Nama', 'Kejuruan', 'Profil_RIASEC', 'Diagnosis_Awal']], 
            hide_index=True,
            use_container_width=True,
            column_config={"Diagnosis_Awal": st.column_config.TextColumn("Diagnosis Awal", help=help_kuadran)}
        )
        
        with st.container(border=True):
            st.markdown("#### 📝 Panel Tindakan Cepat")
            pilih_peserta = st.selectbox("Pilih Peserta untuk Konseling Mindset:", df_pk['Nama'])
            dt = df_pk[df_pk['Nama'] == pilih_peserta].iloc[0]
            
            tab1, tab2 = st.tabs(["📋 Profil RIASEC", "💡 Konseling Motivasi"])
            
            with tab1:
                st.markdown(f"### 📊 Detail Pemetaan Keselarasan Minat: {dt['Nama']}")
                
                riasec_code = str(dt['Kode_RIASEC']).upper()
                if 'C' in riasec_code or 'E' in riasec_code:
                    labels_pekerjaan, nilai_pekerjaan = ["Konsultan Keuangan", "Administrasi", "Analis Bisnis"], [40, 35, 25]
                    minat_text = "Mencari lingkungan kerja yang terstruktur dengan aturan bisnis yang jelas."
                elif 'R' in riasec_code:
                    labels_pekerjaan, nilai_pekerjaan = ["Teknisi Mesin Presisi", "Supervisor Lapangan", "Inspektur"], [45, 30, 25]
                    minat_text = "Lebih produktif berinteraksi dengan benda fisik, alat, dan mesin."
                elif 'I' in riasec_code:
                    labels_pekerjaan, nilai_pekerjaan = ["Analis Sistem", "Peneliti Terapan", "Software Engineer"], [50, 30, 20]
                    minat_text = "Sangat analitis dan menyukai pemecahan masalah teknis mendalam."
                else:
                    labels_pekerjaan, nilai_pekerjaan = ["Spesialis Operasional", "Koordinator Tim", "Fasilitator"], [35, 35, 30]
                    minat_text = "Suka berkolaborasi dalam tim dan membantu operasional berjalan lancar."
                    
                col_chart, col_text = st.columns([1, 1.2])
                with col_chart:
                    fig = px.pie(
                        values=nilai_pekerjaan, 
                        names=labels_pekerjaan, 
                        hole=0.4, 
                        title="Prediksi Person-Environment Fit (SPI)",
                        color_discrete_sequence=px.colors.qualitative.Safe
                    )
                    fig.update_traces(
                        textposition='inside', 
                        textinfo='percent+label',
                        marker=dict(line=dict(color='#ffffff', width=2))
                    )
                    fig.update_layout(
                        showlegend=False, 
                        margin=dict(t=40, b=10, l=10, r=10),
                        font_family="Plus Jakarta Sans",
                        paper_bgcolor='rgba(0,0,0,0)',
                        plot_bgcolor='rgba(0,0,0,0)'
                    )
                    st.plotly_chart(fig, use_container_width=True)
                    
                with col_text:
                    st.info(f"**Kepribadian Pekerja (RIASEC):**\nProfil kepribadian adalah **{dt['Profil_RIASEC']}**.")
                    st.success(f"**Faktor Pendorong Motivasi:**\n{minat_text}")
                    rekomendasi_str = "\n".join([f"{i+1}. {p}" for i, p in enumerate(labels_pekerjaan)])
                    st.warning(f"**Rekomendasi Lingkungan Kerja Alami:**\n\n{rekomendasi_str}")
                    
            with tab2:
                st.markdown(f"#### 🧠 Konseling Mindset & Motivasi: {dt['Nama']}")
                st.caption("Gunakan asisten AI untuk memformulasikan jembatan motivasi antara kejuruan saat ini dengan profil RIASEC bawaan.")
                
                ai_cache_key = f"ai_result_pengantarkerja_{dt['Nama']}"
                if ai_cache_key not in st.session_state:
                    with st.spinner("Menganalisis potensi benturan secara otomatis..."):
                        prompt = f"Peserta {dt['Nama']} ikut kelas ({dt['Kejuruan']}) namun memiliki profil RIASEC {dt['Kode_RIASEC']} ({dt['Profil_RIASEC']}). Buatkan rumusan pertanyaan eksplorasi konseling (career adaptability) untuk menggali faktor protektif dan motivasi intrinsiknya agar ia bisa berhasil di pelatihan ini."
                        
                        # Membangun Smart History (RAG JSON)
                        riwayat = {}
                        if str(dt.get('Catatan_Instruktur', 'nan')) not in ['nan', '-', '']:
                            riwayat['Catatan_Instruktur'] = dt['Catatan_Instruktur']
                            
                        st.session_state[ai_cache_key] = call_ai_guardrailed(prompt, "Pengantar Kerja", riwayat)
                        
                display_ai_result(st.session_state[ai_cache_key])
                        
                catatan = st.text_area("Log Tindakan Intervensi Pengantar Kerja:", placeholder="Misal: Membangun jembatan motivasi dengan menjelaskan bahwa kejuruan ini bisa menjadi pijakan untuk buka usaha mandiri...")
                if st.button("Simpan Keputusan & Tandai Selesai", type="primary"):
                    st.session_state.audit_trail.append(f"[{datetime.now().strftime('%H:%M:%S')}] PENGANTAR KERJA: {pilih_peserta} -> {catatan}")
                    idx = df.index[df['Nama'] == dt['Nama']].tolist()[0]
                    
                    current_note = str(st.session_state.df_peserta.at[idx, 'Catatan_Pengantar_Kerja'])
                    new_note = f"Pengantar Kerja: {catatan}"
                    if current_note == '-' or current_note.strip() == '' or current_note == 'nan': 
                        st.session_state.df_peserta.at[idx, 'Catatan_Pengantar_Kerja'] = new_note
                    else: 
                        st.session_state.df_peserta.at[idx, 'Catatan_Pengantar_Kerja'] = f"{current_note} | {new_note}"
        
                    st.session_state.df_peserta.at[idx, 'Status_Pengantar_Kerja'] = "Sudah Ditangani"
                    
                    success = db.update_record(st.session_state.df_peserta)
                    if success:
                        st.toast(f"Berhasil! Tindakan Pengantar Kerja untuk {pilih_peserta} telah disimpan.", icon="✅")
                    else:
                        st.toast("GAGAL menyimpan ke GSheets.", icon="⚠️")
                        
                    st.rerun() 
    else:
        st.success("🎉 Sempurna! Seluruh risiko kelelahan dan drop-out telah berhasil ditekan.")
        st.balloons()

# ==========================================
# DASBOR 4: SEKSI PEMBERDAYAAN (PENYALURAN)
# ==========================================
elif role == "Seksi Pemberdayaan":
    st.markdown("### 🤝 Dasbor Pemberdayaan: Penempatan Kerja & Inkubasi")
    st.caption("Fokus: Memastikan lulusan BPVP disalurkan ke lingkungan industri yang sejalan dengan kapabilitas dan karakter mereka untuk menekan angka resign dini.")
    
    tab1, tab2 = st.tabs(["🎓 Verifikasi Kompetensi Akhir", "🤝 Matriks Penyaluran & Job Matching"])
    
    with tab1:
        st.markdown("#### 1. Verifikasi Kompetensi Akhir")
        st.info("Pembaruan status kompetensi peserta berdasarkan evaluasi teknis BPVP.")
        
        df_belum_evaluasi = df[df['Status_Kelulusan'] == 'Belum Dievaluasi']
        
        col1, col2 = st.columns([2, 1])
        with col1:
            st.dataframe(df[['Nama', 'Kejuruan', 'Status_Kelulusan']], hide_index=True, use_container_width=True)
            
        with col2:
            if not df_belum_evaluasi.empty:
                peserta_eval = st.selectbox("Pilih Peserta:", df_belum_evaluasi['Nama'])
                status_baru = st.selectbox("Hasil Sertifikasi:", ["Kompeten", "Belum Kompeten"])
                if st.button("Simpan Status", type="primary"):
                    idx = df.index[df['Nama'] == peserta_eval].tolist()[0]
                    st.session_state.df_peserta.at[idx, 'Status_Kelulusan'] = status_baru
                    st.session_state.audit_trail.append(f"[{datetime.now().strftime('%H:%M:%S')}] PEMBERDAYAAN: Input Asesmen {peserta_eval} -> {status_baru}")
                    
                    success = db.update_record(st.session_state.df_peserta)
                    if success: st.success("Status sertifikasi berhasil dienkripsi!")
                    else: st.warning(f"Berhasil di memori, tapi GAGAL simpan ke Sheet.")
                        
                    st.rerun()
            else:
                st.success("Semua peserta BPVP telah disertifikasi.")
                
    with tab2:
        st.markdown("#### 2. Matriks Penyaluran Tenaga Kerja Strategis (Lulusan Kompeten)")
        df_kompeten = df[df['Status_Kelulusan'] == 'Kompeten']
        
        if not df_kompeten.empty:
            mask_belum_salur = df_kompeten['Status_Pemberdayaan'] != "Sudah Disalurkan"
            df_siap_salur = df_kompeten[mask_belum_salur]
            
            if not df_siap_salur.empty:
                st.write("Daftar Alumni BPVP Kompeten (Menunggu Penyaluran Berbasis Data):")
                st.dataframe(
                    df_siap_salur[['Nama', 'Kejuruan', 'Profil_RIASEC', 'Catatan_Instruktur', 'Catatan_Pengantar_Kerja']], 
                    hide_index=True,
                    use_container_width=True
                )
                
                with st.container(border=True):
                    st.markdown("#### 📝 Panel Tindakan Cepat")
                    pilih_peserta_salur = st.selectbox("Pilih Alumni untuk Proses Job Matching (AI):", df_siap_salur['Nama'])
                    dt = df_siap_salur[df_siap_salur['Nama'] == pilih_peserta_salur].iloc[0]
                    
                    ai_cache_key = f"ai_result_pemberdayaan_{dt['Nama']}"
                    if ai_cache_key not in st.session_state:
                        with st.spinner("Mencocokkan arsitektur profil minat secara otomatis..."):
                            prompt = f"""
                            Alumni {dt['Nama']} telah lulus KOMPETEN (Kejuruan {dt['Kejuruan']}).
                            Profil karakter bawaan (RIASEC): {dt['Kode_RIASEC']} ({dt['Profil_RIASEC']}).
                            
                            Tugas Anda: Putuskan 'Person-Environment Fit' TERBAIK untuk alumni ini (Pilih salah satu: 1. Penempatan Kerja Corporate/Pabrik, 2. Pemagangan/OJT, atau 3. Inkubasi Wirausaha Mandiri). Berikan justifikasi analitis yang SANGAT SPESIFIK merujuk pada Konteks Riwayat sebelumnya. Jangan gunakan alasan template.
                            """
                            
                            # Membangun Smart History (RAG JSON)
                            riwayat = {}
                            if str(dt.get('Catatan_Instruktur', 'nan')) not in ['nan', '-', '']:
                                riwayat['Catatan_Instruktur'] = dt['Catatan_Instruktur']
                            if str(dt.get('Catatan_Pengantar_Kerja', 'nan')) not in ['nan', '-', '']:
                                riwayat['Catatan_Pengantar_Kerja'] = dt['Catatan_Pengantar_Kerja']
                                
                            st.session_state[ai_cache_key] = call_ai_guardrailed(prompt, "Seksi Pemberdayaan", riwayat)
                            
                    display_ai_result(st.session_state[ai_cache_key])
                            
                    catatan = st.text_area("Log Surat Keputusan Penyaluran:", placeholder="Misal: Disalurkan secara strategis ke program Inkubasi Wirausaha Mandiri karena tingginya sifat Enterprising...")
                    if st.button("Simpan & Finalisasi Penyaluran", type="primary", key="btn_salur"):
                        st.session_state.audit_trail.append(f"[{datetime.now().strftime('%H:%M:%S')}] PEMBERDAYAAN: {pilih_peserta_salur} -> {catatan}")
                        idx = df.index[df['Nama'] == dt['Nama']].tolist()[0]
                        
                        current_note = str(st.session_state.df_peserta.at[idx, 'Catatan_Pemberdayaan'])
                        new_note = f"Penyaluran: {catatan}"
                        if current_note == '-' or current_note.strip() == '' or current_note == 'nan': 
                            st.session_state.df_peserta.at[idx, 'Catatan_Pemberdayaan'] = new_note
                        else: 
                            st.session_state.df_peserta.at[idx, 'Catatan_Pemberdayaan'] = f"{current_note} | {new_note}"
        
                        st.session_state.df_peserta.at[idx, 'Status_Pemberdayaan'] = "Sudah Disalurkan"
                        
                        success = db.update_record(st.session_state.df_peserta)
                        if success: st.toast(f"Berhasil! Eksekusi Penyaluran Kerja untuk {pilih_peserta} telah ditetapkan.", icon="✅")
                        else: st.toast("GAGAL menyimpan ke GSheets.", icon="⚠️")
                        st.rerun()
            else:
                st.success("🎉 Target BPVP Tercapai! Seluruh lulusan yang kompeten telah disalurkan dengan presisi.")
                st.balloons()
        else:
            st.warning("Belum ada alumni yang kompeten untuk disalurkan.")
            
# ==========================================
# DASBOR 5: PESERTA PELATIHAN (CAREER PASSPORT)
# ==========================================
elif role == "Peserta Pelatihan":
    nama_pilihan = st.session_state.get('nama_peserta', '')
    if not nama_pilihan:
        st.warning("Terjadi kesalahan. Nama peserta tidak ditemukan di sesi ini.")
    else:
        dt = df[df['Nama'] == nama_pilihan].iloc[0]
        
        st.markdown("### 🌟 ADAPTIKA Career Passport Personal Hub")
        st.caption("Eksklusif untuk Anda. Unduh suplemen kompetensi Anda sebagai bekal profesional ke dunia industri.")
        
        col1, col2 = st.columns([1, 1.5])
        with col1:
            st.info(f"**Profil Anda:**\n\n**Nama:** {dt['Nama']}\n**Kompetensi:** {dt['Kejuruan']}\n**Karakter Dominan:** {dt['Profil_RIASEC']}")
            
        with col2:
            status = "Dalam Proses Pendampingan"
            if dt['Status_Instruktur'] == "Sudah Ditangani" and dt['Status_Pengantar_Kerja'] == "Sudah Ditangani":
                status = "Optimal & Siap Kerja"
            elif dt['Status_Instruktur'] == "Sudah Ditangani" or dt['Status_Pengantar_Kerja'] == "Sudah Ditangani":
                status = "Optimalisasi Berjalan"
                
            st.success(f"**Status Kesiapan Terkini:**\n\n✅ {status}\n\n*Anda dinilai memiliki potensi daya juang yang sangat baik untuk terjun ke industri!*")
            
        st.divider()
        st.markdown("#### 📄 Suplemen Kompetensi Talenta (PDF)")
        st.write("Sistem AI kami telah merangkum potensi kekuatan adaptabilitas (Career Adaptability) Anda berdasarkan asesmen. Anda dapat mengunduh dokumen ini untuk dilampirkan pada Curriculum Vitae (CV) Anda.")
        
        if st.button("Generate Dokumen Career Passport", type="primary"):
            with st.spinner("Merumuskan narasi kekuatan profesional (AI)..."):
                prompt = f"""
                Nama Peserta: {dt['Nama']}
                Kejuruan: {dt['Kejuruan']}
                Profil RIASEC Utama: {dt['Profil_RIASEC']} ({dt['Kode_RIASEC']})
                
                Tugas: Buat narasi adaptabilitas positif yang sangat menjual (HR-friendly) tentang bagaimana karakter bawaannya ini justru akan membuat dia sukses mempraktikkan skill {dt['Kejuruan']} di dunia kerja, serta rekomendasikan 2 ekosistem industri spesifik yang cocok untuknya.
                """
                
                # Membangun Smart History (RAG JSON)
                riwayat = {}
                if str(dt.get('Catatan_Instruktur', 'nan')) not in ['nan', '-', '']:
                    riwayat['Catatan_Instruktur'] = dt['Catatan_Instruktur']
                if str(dt.get('Catatan_Pengantar_Kerja', 'nan')) not in ['nan', '-', '']:
                    riwayat['Catatan_Pengantar_Kerja'] = dt['Catatan_Pengantar_Kerja']
                if str(dt.get('Catatan_Pemberdayaan', 'nan')) not in ['nan', '-', '']:
                    riwayat['Catatan_Pemberdayaan'] = dt['Catatan_Pemberdayaan']
                    
                hasil_ai = call_ai_guardrailed(prompt, "Career Passport", riwayat)
                
                if isinstance(hasil_ai, dict) and "narasi_kekuatan" in hasil_ai:
                    narasi = hasil_ai["narasi_kekuatan"]
                    rekomendasi = hasil_ai["rekomendasi_ekosistem"]
                    
                    pdf_bytes = generate_career_passport(
                        nama_peserta=dt['Nama'],
                        kejuruan=dt['Kejuruan'],
                        narasi_kekuatan=narasi,
                        rekomendasi_ekosistem=rekomendasi,
                        skor_numerik=int(dt.get('Skor_Logika_Numerik', 0)),
                        skor_figural=int(dt.get('Skor_Spasial_Figural', 0)),
                        kode_riasec=str(dt.get('Kode_RIASEC', '')),
                        profil_riasec=str(dt.get('Profil_RIASEC', '')),
                        diagnosis=str(dt.get('Diagnosis_Awal', '')),
                    )
                    
                    st.success("Dokumen berhasil dibuat! Silakan unduh di bawah ini.")
                    st.download_button(
                        label="📥 Download ADAPTIKA Career Passport (PDF)",
                        data=pdf_bytes,
                        file_name=f"Career_Passport_{dt['Nama'].replace(' ', '_')}.pdf",
                        mime="application/pdf",
                        use_container_width=True
                    )
                else:
                    st.error("Gagal menghubungkan ke Engine AI. Silakan coba lagi.")

# ------------------------------------------
# HIDE INTERNAL TOOLS FROM PESERTA PELATIHAN
# ------------------------------------------
if role != "Peserta Pelatihan":
    # PERBAIKAN: Laporan unduhan kini ditaruh di luar blok kondisi, sehingga akan SELALU MUNCUL di bagian bawah halaman
    st.divider()
    st.markdown("### 🏛️ Unduh Laporan Indeks Kesiapan BPVP")
    st.caption("📄 Laporan komprehensif mencakup: Cover page, ringkasan eksekutif, chart distribusi kuadran, progress intervensi, peta kesiapan pelatihan, detail peserta per kuadran (teks lengkap), jejak audit, dan lembar pengesahan.")
    try:
        pdf_report_bytes = generate_pdf_report(df)
        st.download_button(
            label="📥 Unduh Laporan Kepala Balai (PDF)", 
            data=pdf_report_bytes, 
            file_name="Laporan_Kesiapan_BPVP.pdf", 
            mime="application/pdf", 
            use_container_width=True,
            type="primary"
        )
    except Exception as e_pdf:
        st.error(f"Gagal membuat laporan PDF: {e_pdf}")

    # ==========================================
    # SISTEM AUDIT TRAIL GLOBAL
    # ==========================================
    st.divider()
    with st.expander("🔐 Lihat Rekam Jejak Audit Sistem"):
        if st.session_state.audit_trail:
            for log in st.session_state.audit_trail:
                st.code(log)
        else:
            st.write("Sistem siap menerima instruksi (*No actions recorded yet*).")