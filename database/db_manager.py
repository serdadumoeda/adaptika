import streamlit as st
import pandas as pd
from streamlit_gsheets import GSheetsConnection

class DatabaseManager:
    """Abstraksi operasi database untuk koneksi Google Sheets secara persisten"""
    
    def __init__(self):
        # 1. Tarik rahasia (secrets) GSheets
        gsheets_secrets = dict(st.secrets["connections"]["gsheets"])
        
        # 2. Perbaiki private_key secara terprogram
        # Jika private_key memuat teks harfiah "\n" (bukan newline asli), kita perbaiki
        fixed_key = gsheets_secrets.get("private_key", "").replace("\\n", "\n")
            
        # 3. Buat koneksi dengan menimpa nilai private_key saja
        self.conn = st.connection("gsheets", type=GSheetsConnection, private_key=fixed_key)
    @st.cache_data(ttl="10m")
    def fetch_all_data(_self) -> pd.DataFrame:
        """Mengambil seluruh data peserta dan me-load ke Pandas DataFrame"""
        try:
            df = _self.conn.read(worksheet="Hasil_ADAPTIKA", usecols=list(range(20)), ttl="10m")
            return df.dropna(how="all")
        except Exception as e:
            st.error(f"Gagal menarik data dari Google Sheets: {e}")
            return pd.DataFrame()
            
    def update_record(self, df: pd.DataFrame) -> bool:
        """Melakukan sinkronisasi DataFrame terbaru langsung ke Google Sheets"""
        try:
            cols_to_drop = [col for col in ['Progress_%', 'Status_Intervensi', 'Catatan_Intervensi', 'Status_Konselor', 'Catatan_Konselor'] if col in df.columns]
            if cols_to_drop:
                df = df.drop(columns=cols_to_drop)
            
            self.conn.update(worksheet="Hasil_ADAPTIKA", data=df)
            st.cache_data.clear() # Invalidasi cache agar data baru terbaca
            return True
        except Exception as e:
            st.error(f"Gagal melakukan proses UPDATE: {e}")
            return False

# Deklarasi Singleton untuk diimpor oleh modul lain
db = DatabaseManager()
