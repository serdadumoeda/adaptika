import pandas as pd
import json
import re

def parse_skillhub_data(df_raw: pd.DataFrame) -> pd.DataFrame:
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
