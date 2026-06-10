import streamlit as st
import pandas as pd
from database.db_manager import db
from services.data_parser import parse_skillhub_data

def fetch_adaptika_data():
    """Fetches data from GSheets or fallback CSV, and parses it if needed."""
    df_saved = db.fetch_all_data()
    
    if not df_saved.empty and 'Diagnosis_Awal' in df_saved.columns:
        df_saved = df_saved.dropna(how='all') 
        cols_to_remove = ['Progress_%', 'Status_Intervensi', 'Catatan_Intervensi', 'Status_Konselor', 'Catatan_Konselor']
        df_saved = df_saved.drop(columns=[c for c in cols_to_remove if c in df_saved.columns])
        
        if 'Status_Instruktur' not in df_saved.columns: df_saved['Status_Instruktur'] = 'Belum Ditangani'
        if 'Catatan_Instruktur' not in df_saved.columns: df_saved['Catatan_Instruktur'] = '-'
        if 'Status_Pengantar_Kerja' not in df_saved.columns: df_saved['Status_Pengantar_Kerja'] = 'Belum Ditangani'
        if 'Catatan_Pengantar_Kerja' not in df_saved.columns: df_saved['Catatan_Pengantar_Kerja'] = '-'
        if 'Status_Pemberdayaan' not in df_saved.columns: df_saved['Status_Pemberdayaan'] = 'Belum Disalurkan'
        if 'Catatan_Pemberdayaan' not in df_saved.columns: df_saved['Catatan_Pemberdayaan'] = '-'
        
        return df_saved
    else:
        CSV_INGEST_URL = "https://docs.google.com/spreadsheets/d/1BxKQenKeUVFV0XEUFBsW-r-cejYUzz76FR7-PI9Y3D0/export?format=csv&gid=2031436800"
        try:
            import urllib.request
            req = urllib.request.Request(
                CSV_INGEST_URL, 
                headers={'User-Agent': 'Mozilla/5.0'}
            )
            with urllib.request.urlopen(req) as response:
                df_raw = pd.read_csv(response)
            return parse_skillhub_data(df_raw)
        except Exception as e_csv:
            try:
                import os
                if os.path.exists("test.csv"):
                    df_raw = pd.read_csv("test.csv")
                    return parse_skillhub_data(df_raw)
                else:
                    raise FileNotFoundError
            except Exception:
                mock_data = [
                    {
                        'Nama': 'Yusuf Prasetyo', 'Kejuruan': 'Teknik Las', 
                        'Skor_Logika_Numerik': 45, 'Skor_Spasial_Figural': 55, 
                        'Kode_RIASEC': 'R-I', 'Profil_RIASEC': 'Realistis-Investigatif',
                        'Detail_SiapLatih': '{"detail":{"test_result":[{"name":"Pengetahuan Umum","value":60},{"name":"Kemampuan Verbal","value":50},{"name":"Logika Numerik","value":45},{"name":"Spasial Figural","value":55}]}}',
                        'Status_Kelulusan': 'Belum Dievaluasi', 'Status_Instruktur': 'Belum Ditangani', 'Catatan_Instruktur': '-',
                        'Status_Pengantar_Kerja': 'Belum Ditangani', 'Catatan_Pengantar_Kerja': '-',
                        'Status_Pemberdayaan': 'Belum Disalurkan', 'Catatan_Pemberdayaan': '-'
                    },
                    {
                        'Nama': 'Budi Santoso', 'Kejuruan': 'Teknik Listrik', 
                        'Skor_Logika_Numerik': 70, 'Skor_Spasial_Figural': 50, 
                        'Kode_RIASEC': 'R', 'Profil_RIASEC': 'Realistis',
                        'Detail_SiapLatih': '{"detail":{"test_result":[{"name":"Pengetahuan Umum","value":75},{"name":"Kemampuan Verbal","value":65},{"name":"Logika Numerik","value":70},{"name":"Spasial Figural","value":50}]}}',
                        'Status_Kelulusan': 'Belum Dievaluasi', 'Status_Instruktur': 'Belum Ditangani', 'Catatan_Instruktur': '-',
                        'Status_Pengantar_Kerja': 'Belum Ditangani', 'Catatan_Pengantar_Kerja': '-',
                        'Status_Pemberdayaan': 'Belum Disalurkan', 'Catatan_Pemberdayaan': '-'
                    },
                    {
                        'Nama': 'Siti Aminah', 'Kejuruan': 'Pemrograman Web', 
                        'Skor_Logika_Numerik': 55, 'Skor_Spasial_Figural': 70, 
                        'Kode_RIASEC': 'I-C', 'Profil_RIASEC': 'Investigatif-Konvensional',
                        'Detail_SiapLatih': '{"detail":{"test_result":[{"name":"Pengetahuan Umum","value":80},{"name":"Kemampuan Verbal","value":75},{"name":"Logika Numerik","value":55},{"name":"Spasial Figural","value":70}]}}',
                        'Status_Kelulusan': 'Belum Dievaluasi', 'Status_Instruktur': 'Belum Ditangani', 'Catatan_Instruktur': '-',
                        'Status_Pengantar_Kerja': 'Belum Ditangani', 'Catatan_Pengantar_Kerja': '-',
                        'Status_Pemberdayaan': 'Belum Disalurkan', 'Catatan_Pemberdayaan': '-'
                    },
                    {
                        'Nama': 'Roni Wijaya', 'Kejuruan': 'Teknik Las', 
                        'Skor_Logika_Numerik': 40, 'Skor_Spasial_Figural': 45, 
                        'Kode_RIASEC': 'C', 'Profil_RIASEC': 'Konvensional',
                        'Detail_SiapLatih': '{"detail":{"test_result":[{"name":"Pengetahuan Umum","value":50},{"name":"Kemampuan Verbal","value":40},{"name":"Logika Numerik","value":40},{"name":"Spasial Figural","value":45}]}}',
                        'Status_Kelulusan': 'Belum Dievaluasi', 'Status_Instruktur': 'Belum Ditangani', 'Catatan_Instruktur': '-',
                        'Status_Pengantar_Kerja': 'Belum Ditangani', 'Catatan_Pengantar_Kerja': '-',
                        'Status_Pemberdayaan': 'Belum Disalurkan', 'Catatan_Pemberdayaan': '-'
                    },
                    {
                        'Nama': 'Dewi Lestari', 'Kejuruan': 'Pemrograman Web', 
                        'Skor_Logika_Numerik': 85, 'Skor_Spasial_Figural': 80, 
                        'Kode_RIASEC': 'E-A', 'Profil_RIASEC': 'Enterprising-Artistik',
                        'Detail_SiapLatih': '{"detail":{"test_result":[{"name":"Pengetahuan Umum","value":90},{"name":"Kemampuan Verbal","value":85},{"name":"Logika Numerik","value":85},{"name":"Spasial Figural","value":80}]}}',
                        'Status_Kelulusan': 'Belum Dievaluasi', 'Status_Instruktur': 'Belum Ditangani', 'Catatan_Instruktur': '-',
                        'Status_Pengantar_Kerja': 'Belum Ditangani', 'Catatan_Pengantar_Kerja': '-',
                        'Status_Pemberdayaan': 'Belum Disalurkan', 'Catatan_Pemberdayaan': '-'
                    }
                ]
                df_fallback = pd.DataFrame(mock_data)
                df_fallback['Diagnosis_Awal'] = "Kuadran 1 (Kapasitas Mumpuni)" 
                return df_fallback

def render_login_gateway():
    st.markdown(
        """
        <div style="text-align: center; margin-bottom: 2rem; margin-top: 3rem;">
            <img src="https://kemnaker.go.id/assets/images/logo-color.png" style="height: 90px; margin-bottom: 1.5rem;">
            <h1 style="color: var(--text-color); font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; letter-spacing: -0.025em;">ADAPTIKA Enterprise</h1>
            <p style="color: var(--text-color); opacity: 0.8; font-size: 1.15rem; margin-top: 0; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                Sistem Analisis Gap Kesiapan Pelatihan & Keselarasan Minat Pelatihan Berbasis Data Asesmen
            </p>
        </div>
        """,
        unsafe_allow_html=True
    )
    
    col1, col2, col3 = st.columns([1, 1.5, 1])
    with col2:
        st.markdown(
            """
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--text-color); font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Portal Masuk Satu Pintu (SSO)</h3>
                <p style="color: var(--text-color); opacity: 0.8; font-size: 0.9rem; margin-top: 0;">Silakan pilih peran operator Anda untuk masuk ke dasbor</p>
            </div>
            """,
            unsafe_allow_html=True
        )
        
        role_choice = st.selectbox("Akses Sistem Sebagai:", 
            ["Penyelenggara Pelatihan", "Instruktur Teknis", "Pengantar Kerja", "Seksi Pemberdayaan", "Peserta Pelatihan"]
        )
        
        nama_peserta = None
        if role_choice == "Peserta Pelatihan":
            if "df_peserta" not in st.session_state or st.session_state.df_peserta.empty:
                with st.spinner("Mengambil data peserta..."):
                    try:
                        st.session_state.df_peserta = fetch_adaptika_data()
                    except Exception as e:
                        st.error(f"Gagal menarik data: {e}")
            
            if "df_peserta" in st.session_state and not st.session_state.df_peserta.empty:
                nama_list = st.session_state.df_peserta['Nama'].tolist()
                nama_peserta = st.selectbox("Pilih Nama Anda:", nama_list)
        
        st.markdown("<div style='margin-top: 1.5rem;'></div>", unsafe_allow_html=True)
        
        if st.button("Masuk (Login)", type="primary", use_container_width=True):
            with st.spinner("Mencari riwayat data tersimpan..."):
                try:
                    if role_choice != "Peserta Pelatihan":
                        st.session_state.df_peserta = fetch_adaptika_data()
                    
                    st.session_state.logged_in = True
                    st.session_state.role = role_choice
                    if nama_peserta:
                        st.session_state.nama_peserta = nama_peserta
                    st.rerun()
                except Exception as e:
                    st.error(f"Gagal menarik data: {e}. Pastikan koneksi stabil.")
    st.stop()
