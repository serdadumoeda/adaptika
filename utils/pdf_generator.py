import streamlit as st
from datetime import datetime
from fpdf import FPDF
import pandas as pd
import tempfile
import os

# =========================================================================
# UTILITY
# =========================================================================

def clean_pdf_text(text):
    if not isinstance(text, str):
        text = str(text)
    replacements = {
        '\u2018': "'", '\u2019': "'", '\u201c': '"', '\u201d': '"',
        '\u2013': '-', '\u2014': '-', '\u2022': '*', '\u20ac': 'EUR',
        '\u2192': '->', '\u2190': '<-', '\u2705': '[v]', '\u274c': '[x]',
        '\u26a0': '[!]', '\u2139': '[i]',
    }
    for orig, rep in replacements.items():
        text = text.replace(orig, rep)
    return text.encode('latin1', errors='ignore').decode('latin1')


# =========================================================================
# CHART GENERATION (matplotlib -> temp PNG)
# =========================================================================

def _generate_pie_chart(q1, q2, q3, q4, total, tmp_dir):
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    
    labels = [
        f'K1: Kapasitas\nMumpuni ({q1})',
        f'K2: Perlu\nPendampingan ({q2})',
        f'K3: Risiko\nKelelahan ({q3})',
        f'K4: Perlu Perhatian\nKhusus ({q4})'
    ]
    sizes = [q1, q2, q3, q4]
    colors = ['#10b981', '#f59e0b', '#f97316', '#ef4444']
    explode = (0.03, 0.03, 0.03, 0.03)
    
    fig, ax = plt.subplots(figsize=(7, 4.5))
    wedges, texts, autotexts = ax.pie(
        sizes, explode=explode, labels=labels, colors=colors,
        autopct=lambda pct: f'{pct:.0f}%' if pct > 0 else '',
        startangle=90, textprops={'fontsize': 9}, pctdistance=0.75
    )
    for at in autotexts:
        at.set_fontsize(11)
        at.set_fontweight('bold')
        at.set_color('white')
    ax.set_title(f'Distribusi Kuadran Kesiapan Kerja\n(Total: {total} Peserta)',
                 fontsize=13, fontweight='bold', pad=15)
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'chart_pie.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def _generate_bar_progress_chart(df, tmp_dir):
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    import numpy as np
    
    inst_done = len(df[df['Status_Instruktur'] == 'Sudah Ditangani'])
    inst_pending = len(df[df['Status_Instruktur'] == 'Belum Ditangani'])
    pk_done = len(df[df['Status_Pengantar_Kerja'] == 'Sudah Ditangani'])
    pk_pending = len(df[df['Status_Pengantar_Kerja'] == 'Belum Ditangani'])
    kompeten = len(df[df['Status_Kelulusan'] == 'Kompeten'])
    belum_eval = len(df[df['Status_Kelulusan'] == 'Belum Dievaluasi'])
    belum_kompeten = len(df[df['Status_Kelulusan'] == 'Belum Kompeten'])
    
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4))
    
    categories = ['Instruktur', 'Pengantar Kerja']
    done = [inst_done, pk_done]
    pending = [inst_pending, pk_pending]
    x = np.arange(len(categories))
    width = 0.35
    bars1 = ax1.bar(x - width/2, done, width, label='Sudah Ditangani', color='#10b981')
    bars2 = ax1.bar(x + width/2, pending, width, label='Belum Ditangani', color='#f87171')
    ax1.set_ylabel('Jumlah Peserta')
    ax1.set_title('Progress Intervensi', fontweight='bold', fontsize=11)
    ax1.set_xticks(x)
    ax1.set_xticklabels(categories)
    ax1.legend(fontsize=8)
    ax1.bar_label(bars1, padding=2, fontsize=9)
    ax1.bar_label(bars2, padding=2, fontsize=9)
    ax1.set_ylim(0, max(max(done + [1]), max(pending + [1])) * 1.3 + 1)
    ax1.spines['top'].set_visible(False)
    ax1.spines['right'].set_visible(False)
    
    k_labels = ['Kompeten', 'Belum\nDievaluasi', 'Belum\nKompeten']
    k_values = [kompeten, belum_eval, belum_kompeten]
    k_colors = ['#10b981', '#94a3b8', '#f87171']
    bars3 = ax2.bar(k_labels, k_values, color=k_colors, width=0.5)
    ax2.set_title('Status Kelulusan', fontweight='bold', fontsize=11)
    ax2.set_ylabel('Jumlah Peserta')
    ax2.bar_label(bars3, padding=2, fontsize=9)
    ax2.set_ylim(0, max(k_values + [1]) * 1.3 + 1)
    ax2.spines['top'].set_visible(False)
    ax2.spines['right'].set_visible(False)
    
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'chart_bar.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def _generate_scatter_chart(df, tmp_dir):
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    from matplotlib.lines import Line2D
    
    fig, ax = plt.subplots(figsize=(8, 5))
    
    color_map = {
        'Kuadran 1': '#10b981', 'Kuadran 2': '#f59e0b',
        'Kuadran 3': '#f97316', 'Kuadran 4': '#ef4444',
    }
    
    for _, row in df.iterrows():
        diag = str(row.get('Diagnosis_Awal', ''))
        color = '#94a3b8'
        for key, c in color_map.items():
            if key in diag:
                color = c
                break
        ax.scatter(row['Skor_Logika_Numerik'], row['Skor_Spasial_Figural'],
                   c=color, s=50, alpha=0.8, edgecolors='white', linewidth=0.5)
    
    ax.axhline(y=60, color='#cbd5e1', linestyle='--', linewidth=1, alpha=0.7)
    ax.axvline(x=60, color='#cbd5e1', linestyle='--', linewidth=1, alpha=0.7)
    ax.text(80, 85, 'K1: Kapasitas\nMumpuni', fontsize=8, ha='center',
            color='#10b981', fontweight='bold', alpha=0.7)
    ax.text(30, 85, 'K3: Risiko\nKelelahan', fontsize=8, ha='center',
            color='#f97316', fontweight='bold', alpha=0.7)
    ax.text(80, 25, 'K2: Perlu\nPendampingan', fontsize=8, ha='center',
            color='#f59e0b', fontweight='bold', alpha=0.7)
    ax.text(30, 25, 'K4: Perlu Perhatian\nKhusus', fontsize=8, ha='center',
            color='#ef4444', fontweight='bold', alpha=0.7)
    
    ax.set_xlabel('Skor Logika Numerik', fontsize=10)
    ax.set_ylabel('Skor Spasial Figural', fontsize=10)
    ax.set_title('Peta Persebaran Kapasitas Kognitif Peserta', fontweight='bold', fontsize=12)
    ax.set_xlim(-5, 105)
    ax.set_ylim(-5, 105)
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.grid(True, alpha=0.2)
    
    legend_elements = [
        Line2D([0], [0], marker='o', color='w', markerfacecolor='#10b981', markersize=8, label='K1'),
        Line2D([0], [0], marker='o', color='w', markerfacecolor='#f59e0b', markersize=8, label='K2'),
        Line2D([0], [0], marker='o', color='w', markerfacecolor='#f97316', markersize=8, label='K3'),
        Line2D([0], [0], marker='o', color='w', markerfacecolor='#ef4444', markersize=8, label='K4'),
    ]
    ax.legend(handles=legend_elements, loc='lower right', fontsize=8, framealpha=0.9)
    
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'chart_scatter.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def _generate_kejuruan_chart(df, tmp_dir):
    """Generate a stacked bar chart by kejuruan."""
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    import numpy as np
    
    kejuruan_list = df['Kejuruan'].unique()
    if len(kejuruan_list) < 2:
        return None
    
    q_labels = ['Kuadran 1', 'Kuadran 2', 'Kuadran 3', 'Kuadran 4']
    q_colors = ['#10b981', '#f59e0b', '#f97316', '#ef4444']
    
    fig, ax = plt.subplots(figsize=(10, 4))
    
    # Shorten kejuruan names for display
    short_names = []
    for kj in kejuruan_list:
        name = str(kj)
        if len(name) > 30:
            name = name[:28] + '..'
        short_names.append(name)
    
    x = np.arange(len(kejuruan_list))
    bottom = np.zeros(len(kejuruan_list))
    
    for q_label, color in zip(q_labels, q_colors):
        values = []
        for kj in kejuruan_list:
            count = len(df[(df['Kejuruan'] == kj) & (df['Diagnosis_Awal'].str.contains(q_label, na=False))])
            values.append(count)
        bars = ax.bar(x, values, bottom=bottom, label=q_label, color=color, width=0.5)
        # Add value labels on bars
        for bar, val in zip(bars, values):
            if val > 0:
                ax.text(bar.get_x() + bar.get_width()/2, bar.get_y() + bar.get_height()/2,
                       str(val), ha='center', va='center', fontsize=8, fontweight='bold', color='white')
        bottom += np.array(values)
    
    ax.set_xticks(x)
    ax.set_xticklabels(short_names, rotation=15, ha='right', fontsize=8)
    ax.set_ylabel('Jumlah Peserta')
    ax.set_title('Distribusi Risiko Kesiapan per Kejuruan', fontweight='bold', fontsize=12)
    ax.legend(fontsize=8, loc='upper right')
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'chart_kejuruan.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


# =========================================================================
# PROPER MULTI-CELL ROW RENDERING
# =========================================================================

def _count_lines_in_cell(pdf, text, col_width):
    """Count how many lines text will take in a given column width using FPDF's get_string_width."""
    if not text or text.strip() == '':
        return 1
    
    text = clean_pdf_text(text)
    usable_w = col_width - 2  # 1mm padding each side
    
    lines = 0
    for paragraph in text.split('\n'):
        if not paragraph:
            lines += 1
            continue
        # Measure word-by-word
        words = paragraph.split(' ')
        current_line = ''
        for word in words:
            test = current_line + (' ' if current_line else '') + word
            if pdf.get_string_width(test) > usable_w and current_line:
                lines += 1
                current_line = word
            else:
                current_line = test
        if current_line:
            lines += 1
    
    return max(1, lines)


def _render_multicell_row(pdf, data, col_widths, line_h, fill_color=None, is_header=False):
    """
    Render a row where each cell can have multi-line text.
    
    1. First pass: measure required height for each cell
    2. Draw all cell rectangles at the max height
    3. Write text inside each cell with multi_cell
    """
    x_start = pdf.get_x()
    y_start = pdf.get_y()
    
    # Set font for measurement
    if is_header:
        pdf.set_font('Arial', 'B', 7)
    else:
        pdf.set_font('Arial', '', 7)
    
    # First pass: calculate max row height
    max_lines = 1
    for text, w in zip(data, col_widths):
        n = _count_lines_in_cell(pdf, text, w)
        max_lines = max(max_lines, n)
    
    row_h = max(7, max_lines * line_h + 2)  # +2mm padding
    
    # Draw cell rectangles
    for i, w in enumerate(col_widths):
        x = x_start + sum(col_widths[:i])
        
        if fill_color:
            pdf.set_fill_color(*fill_color)
            pdf.rect(x, y_start, w, row_h, 'DF')
        else:
            pdf.rect(x, y_start, w, row_h, 'D')
    
    # Second pass: write text in each cell
    if is_header:
        pdf.set_font('Arial', 'B', 7)
    else:
        pdf.set_font('Arial', '', 7)
    
    for i, (text, w) in enumerate(zip(data, col_widths)):
        x = x_start + sum(col_widths[:i])
        align = 'C' if i in [0, 3, 4, 5] else 'L'
        
        # Position cursor inside cell with padding
        pdf.set_xy(x + 1, y_start + 1)
        pdf.multi_cell(w - 2, line_h, clean_pdf_text(text), 0, align)
    
    # Move cursor below the row
    pdf.set_xy(x_start, y_start + row_h)
    return row_h


# =========================================================================
# MAIN: LAPORAN KEPALA BALAI (COMPREHENSIVE)
# =========================================================================

@st.cache_data
def generate_pdf_report(df: pd.DataFrame):
    """Generate a comprehensive PDF report for Kepala Balai."""
    
    tmp_dir = tempfile.mkdtemp()
    
    # Pre-compute stats
    total = len(df)
    q1 = len(df[df['Diagnosis_Awal'].str.contains("Kuadran 1", na=False)])
    q2 = len(df[df['Diagnosis_Awal'].str.contains("Kuadran 2", na=False)])
    q3 = len(df[df['Diagnosis_Awal'].str.contains("Kuadran 3", na=False)])
    q4 = len(df[df['Diagnosis_Awal'].str.contains("Kuadran 4", na=False)])
    
    inst_done = len(df[df['Status_Instruktur'] == 'Sudah Ditangani'])
    pk_done = len(df[df['Status_Pengantar_Kerja'] == 'Sudah Ditangani'])
    kompeten = len(df[df['Status_Kelulusan'] == 'Kompeten'])
    
    kejuruan_list = df['Kejuruan'].unique().tolist()
    kejuruan_str = ", ".join(kejuruan_list) if kejuruan_list else "Tidak Diketahui"
    
    # Generate charts
    try:
        pie_path = _generate_pie_chart(q1, q2, q3, q4, total, tmp_dir)
    except Exception:
        pie_path = None
    try:
        bar_path = _generate_bar_progress_chart(df, tmp_dir)
    except Exception:
        bar_path = None
    try:
        scatter_path = _generate_scatter_chart(df, tmp_dir)
    except Exception:
        scatter_path = None
    try:
        kejuruan_path = _generate_kejuruan_chart(df, tmp_dir)
    except Exception:
        kejuruan_path = None
    
    # =====================================================================
    # PDF CLASS (Landscape A4)
    # =====================================================================
    class ReportPDF(FPDF):
        def header(self):
            if self.page_no() == 1:
                return
            self.set_fill_color(15, 23, 42)
            self.rect(0, 0, 297, 22, 'F')
            self.set_text_color(255, 255, 255)
            self.set_font('Arial', 'B', 11)
            self.text(10, 10, clean_pdf_text('ADAPTIKA'))
            self.set_font('Arial', '', 8)
            self.text(10, 16, clean_pdf_text('Laporan Kesiapan Kerja Peserta Pelatihan Vokasi'))
            self.text(230, 10, clean_pdf_text(f'Tanggal: {datetime.now().strftime("%d %B %Y")}'))
            self.text(230, 16, clean_pdf_text(f'Total Peserta: {total}'))
            self.set_draw_color(99, 102, 241)
            self.set_line_width(0.8)
            self.line(0, 22, 297, 22)
            self.ln(18)
        
        def footer(self):
            self.set_y(-12)
            self.set_font('Arial', 'I', 7)
            self.set_text_color(100, 116, 139)
            self.cell(0, 8, clean_pdf_text(f'Halaman {self.page_no()}'), 0, 0, 'C')
            self.set_x(200)
            self.cell(0, 8, clean_pdf_text('ADAPTIKA - Rahasia & Internal BPVP'), 0, 0, 'R')
    
    pdf = ReportPDF('L', 'mm', 'A4')
    pdf.set_auto_page_break(auto=True, margin=18)
    
    # Table column config
    #                 No   Nama  Kejuruan Num  Fig  RIASEC Status     Instr Note  PK Note
    col_w = [8, 40, 42, 14, 14, 16, 32, 55, 56]
    col_headers = ['No', 'Nama Peserta', 'Kejuruan', 'Num', 'Fig', 'RIASEC',
                   'Status Intervensi', 'Catatan Instruktur', 'Catatan Pengantar Kerja']
    LINE_H = 3.5  # line height in multi_cell
    
    # =====================================================================
    # PAGE 1: COVER
    # =====================================================================
    pdf.add_page()
    pdf.set_fill_color(15, 23, 42)
    pdf.rect(0, 0, 297, 210, 'F')
    pdf.set_fill_color(99, 102, 241)
    pdf.rect(0, 0, 297, 4, 'F')
    
    pdf.set_text_color(255, 255, 255)
    pdf.set_font('Arial', 'B', 32)
    pdf.set_y(55)
    pdf.cell(0, 15, clean_pdf_text('LAPORAN KEPALA BALAI'), 0, 1, 'C')
    pdf.set_font('Arial', '', 14)
    pdf.set_text_color(148, 163, 184)
    pdf.cell(0, 8, clean_pdf_text('Indeks Kesiapan Kerja & Analisis Intervensi Peserta Pelatihan'), 0, 1, 'C')
    
    pdf.set_draw_color(99, 102, 241)
    pdf.set_line_width(1)
    pdf.line(100, 90, 197, 90)
    
    pdf.set_y(100)
    pdf.set_font('Arial', 'B', 12)
    pdf.set_text_color(129, 140, 248)
    pdf.cell(0, 8, clean_pdf_text('ADAPTIKA'), 0, 1, 'C')
    pdf.set_font('Arial', '', 10)
    pdf.set_text_color(148, 163, 184)
    pdf.cell(0, 6, clean_pdf_text('Adaptive Talent Intelligence & Kesiapan Analitik'), 0, 1, 'C')
    pdf.ln(10)
    pdf.set_text_color(203, 213, 225)
    pdf.cell(0, 6, clean_pdf_text(f'Kejuruan: {kejuruan_str}'), 0, 1, 'C')
    pdf.cell(0, 6, clean_pdf_text(f'Jumlah Peserta: {total} Orang'), 0, 1, 'C')
    pdf.cell(0, 6, clean_pdf_text(f'Tanggal Cetak: {datetime.now().strftime("%d %B %Y, %H:%M WIB")}'), 0, 1, 'C')
    pdf.ln(8)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(100, 116, 139)
    pdf.cell(0, 6, clean_pdf_text('Balai Pelatihan Vokasi dan Produktivitas (BPVP) Surakarta'), 0, 1, 'C')
    pdf.cell(0, 6, clean_pdf_text('Kementerian Ketenagakerjaan Republik Indonesia'), 0, 1, 'C')
    pdf.set_fill_color(6, 182, 212)
    pdf.rect(0, 206, 297, 4, 'F')
    
    # =====================================================================
    # PAGE 2: RINGKASAN EKSEKUTIF + PIE
    # =====================================================================
    pdf.add_page()
    pdf.set_font('Arial', 'B', 16)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('I. RINGKASAN EKSEKUTIF'), 0, 1, 'L')
    pdf.ln(2)
    
    pdf.set_font('Arial', '', 10)
    pdf.set_text_color(51, 65, 85)
    summary = (
        f"Laporan ini menyajikan analisis kesiapan kerja terhadap {total} peserta pelatihan vokasi "
        f"berdasarkan data tes kemampuan berpikir (kognitif) dan tes kepribadian RIASEC yang diperoleh "
        f"dari platform SIAPKerja Kementerian Ketenagakerjaan. Peserta dikelompokkan ke dalam 4 kuadran "
        f"kesiapan berdasarkan teori Holland (1959), Schmidt & Hunter (1998), dan Kristof-Brown (2005)."
    )
    pdf.multi_cell(0, 5.5, clean_pdf_text(summary))
    pdf.ln(4)
    
    # Stats boxes + pie chart side by side
    col_stats_x = 10
    y_start = pdf.get_y()
    box_w = 118
    box_h = 18
    
    boxes = [
        (q1, 'Kuadran 1 - Kapasitas Mumpuni', 'Siap optimal untuk industri. Prioritas penempatan langsung.',
         (209,250,229), (16,185,129), (6,95,70)),
        (q2, 'Kuadran 2 - Perlu Pendampingan', 'Berikan scaffolding bertahap oleh Instruktur.',
         (254,243,199), (245,158,11), (146,64,14)),
        (q3, 'Kuadran 3 - Risiko Kelelahan', 'Arahkan ke posisi industri yang sesuai kepribadian.',
         (255,237,213), (249,115,22), (154,52,18)),
        (q4, 'Kuadran 4 - Perlu Perhatian Khusus', 'Eksplorasi perpindahan kejuruan sebelum terlambat.',
         (254,226,226), (239,68,68), (153,27,27)),
    ]
    
    for i, (count, title, desc, bg, border, text_c) in enumerate(boxes):
        y = y_start + i * (box_h + 3)
        pdf.set_fill_color(*bg)
        pdf.set_draw_color(*border)
        pdf.rect(col_stats_x, y, box_w, box_h, 'DF')
        pdf.set_xy(col_stats_x + 3, y + 2)
        pdf.set_font('Arial', 'B', 9)
        pdf.set_text_color(*text_c)
        pdf.cell(0, 5, clean_pdf_text(f'{title}'), 0, 1)
        pdf.set_x(col_stats_x + 3)
        pdf.set_font('Arial', '', 8)
        pct = count * 100 // max(total, 1)
        pdf.set_text_color(*text_c)
        pdf.cell(30, 5, clean_pdf_text(f'{count} peserta ({pct}%)'), 0, 0)
        pdf.set_text_color(51, 65, 85)
        pdf.cell(0, 5, clean_pdf_text(f'| {desc}'), 0, 1)
    
    if pie_path and os.path.exists(pie_path):
        pdf.image(pie_path, 135, y_start, 150)
    
    # =====================================================================
    # PAGE 3: CHARTS
    # =====================================================================
    pdf.add_page()
    pdf.set_font('Arial', 'B', 16)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('II. ANALISIS VISUAL'), 0, 1, 'L')
    pdf.ln(2)
    
    # Bar chart
    pdf.set_font('Arial', 'B', 12)
    pdf.set_text_color(51, 65, 85)
    pdf.cell(0, 8, clean_pdf_text('A. Progress Intervensi & Status Kelulusan'), 0, 1, 'L')
    pdf.set_font('Arial', '', 9)
    pdf.multi_cell(0, 5, clean_pdf_text(
        f"Dari {total} peserta, Instruktur telah menangani {inst_done} peserta ({inst_done*100//max(total,1)}%), "
        f"sementara Pengantar Kerja telah menangani {pk_done} peserta ({pk_done*100//max(total,1)}%). "
        f"Status kelulusan: {kompeten} peserta dinyatakan Kompeten."
    ))
    pdf.ln(2)
    
    if bar_path and os.path.exists(bar_path):
        pdf.image(bar_path, 30, pdf.get_y(), 230)
        pdf.ln(62)
    
    # Scatter chart
    pdf.set_font('Arial', 'B', 12)
    pdf.set_text_color(51, 65, 85)
    pdf.cell(0, 8, clean_pdf_text('B. Peta Persebaran Kapasitas Kognitif'), 0, 1, 'L')
    pdf.set_font('Arial', '', 9)
    pdf.multi_cell(0, 5, clean_pdf_text(
        "Grafik berikut menampilkan posisi setiap peserta berdasarkan skor Logika Numerik (sumbu X) "
        "dan Spasial Figural (sumbu Y). Garis putus-putus di skor 60 menunjukkan ambang batas."
    ))
    pdf.ln(2)
    
    if scatter_path and os.path.exists(scatter_path):
        y_pos = pdf.get_y()
        remaining = 210 - 18 - y_pos
        img_h = min(remaining - 5, 75)
        pdf.image(scatter_path, 40, y_pos, 210, img_h)
    
    # Kejuruan chart (if multiple kejuruan)
    if kejuruan_path and os.path.exists(kejuruan_path):
        pdf.add_page()
        pdf.set_font('Arial', 'B', 12)
        pdf.set_text_color(51, 65, 85)
        pdf.cell(0, 8, clean_pdf_text('C. Distribusi Risiko per Kejuruan'), 0, 1, 'L')
        pdf.set_font('Arial', '', 9)
        pdf.multi_cell(0, 5, clean_pdf_text(
            "Grafik menampilkan distribusi kuadran kesiapan untuk masing-masing program kejuruan, "
            "membantu identifikasi kejuruan yang membutuhkan perhatian manajemen."
        ))
        pdf.ln(2)
        pdf.image(kejuruan_path, 25, pdf.get_y(), 245)
    
    # =====================================================================
    # DETAIL PER KUADRAN
    # =====================================================================
    pdf.add_page()
    pdf.set_font('Arial', 'B', 16)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('III. DETAIL EVALUASI PESERTA PER KUADRAN'), 0, 1, 'L')
    pdf.ln(2)
    
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(51, 65, 85)
    pdf.multi_cell(0, 5, clean_pdf_text(
        "Tabel berikut menampilkan detail lengkap seluruh peserta yang dikelompokkan berdasarkan "
        "kuadran kesiapan. Seluruh catatan instruktur dan pengantar kerja ditampilkan lengkap tanpa diringkas."
    ))
    pdf.ln(4)
    
    def render_table_header_row(pdf):
        """Render the table header."""
        pdf.set_draw_color(30, 41, 59)
        _render_multicell_row(
            pdf, col_headers, col_w, LINE_H,
            fill_color=(30, 41, 59), is_header=True
        )
        # Reset text color after header (which uses white from fill)
        pdf.set_text_color(15, 23, 42)
    
    quadrant_config = [
        ("Kuadran 1", "Kapasitas Mumpuni (Mampu & Cocok)", (209,250,229), (6,95,70)),
        ("Kuadran 2", "Perlu Pendampingan (Belum Mampu tapi Cocok)", (254,243,199), (146,64,14)),
        ("Kuadran 3", "Risiko Kelelahan (Mampu tapi Tidak Cocok)", (255,237,213), (154,52,18)),
        ("Kuadran 4", "Perlu Perhatian Khusus (Belum Mampu & Tidak Cocok)", (254,226,226), (153,27,27)),
    ]
    
    for q_key, q_label, bg_color, text_color in quadrant_config:
        df_q = df[df['Diagnosis_Awal'].str.contains(q_key, na=False)]
        
        if pdf.get_y() > 160:
            pdf.add_page()
        
        # Section header banner
        pdf.set_fill_color(*bg_color)
        pdf.set_draw_color(*text_color)
        y_banner = pdf.get_y()
        pdf.rect(10, y_banner, 277, 9, 'DF')
        pdf.set_xy(12, y_banner + 1)
        pdf.set_font('Arial', 'B', 10)
        pdf.set_text_color(*text_color)
        pdf.cell(0, 6, clean_pdf_text(f'{q_key} - {q_label}  ({len(df_q)} peserta)'), 0, 1)
        pdf.ln(2)
        
        if df_q.empty:
            pdf.set_font('Arial', 'I', 9)
            pdf.set_text_color(100, 116, 139)
            pdf.cell(0, 6, clean_pdf_text('Tidak ada peserta di kuadran ini.'), 0, 1)
            pdf.ln(4)
            continue
        
        # Table header
        pdf.set_text_color(255, 255, 255)
        render_table_header_row(pdf)
        
        # Table rows
        pdf.set_draw_color(226, 232, 240)
        row_num = 1
        for _, row in df_q.iterrows():
            # Prepare cell data - FULL TEXT, no truncation
            nama = str(row.get('Nama', '-'))
            kejuruan = str(row.get('Kejuruan', '-'))
            num = str(int(row.get('Skor_Logika_Numerik', 0)))
            fig_val = str(int(row.get('Skor_Spasial_Figural', 0)))
            riasec = str(row.get('Kode_RIASEC', '-'))
            
            st_k = str(row.get('Status_Kelulusan', '-'))
            st_i = str(row.get('Status_Instruktur', '-'))
            st_pk = str(row.get('Status_Pengantar_Kerja', '-'))
            status = f"Lulus: {st_k}\nInstr: {st_i}\nPK: {st_pk}"
            
            c_ins = str(row.get('Catatan_Instruktur', '-'))
            c_pk = str(row.get('Catatan_Pengantar_Kerja', '-'))
            if c_ins in ['-', 'nan', '', 'None']: c_ins = '-'
            if c_pk in ['-', 'nan', '', 'None']: c_pk = '-'
            
            data = [str(row_num), nama, kejuruan, num, fig_val, riasec, status, c_ins, c_pk]
            
            # Check if we need a page break BEFORE measuring (estimate)
            pdf.set_font('Arial', '', 7)
            max_lines = 1
            for text, w in zip(data, col_w):
                n = _count_lines_in_cell(pdf, text, w)
                max_lines = max(max_lines, n)
            est_h = max(7, max_lines * LINE_H + 2)
            
            if pdf.get_y() + est_h > 190:
                pdf.add_page()
                pdf.set_text_color(255, 255, 255)
                render_table_header_row(pdf)
                pdf.set_draw_color(226, 232, 240)
            
            # Alternate row colors
            fill = (248, 250, 252) if row_num % 2 == 0 else (255, 255, 255)
            pdf.set_text_color(15, 23, 42)
            _render_multicell_row(pdf, data, col_w, LINE_H, fill_color=fill)
            
            row_num += 1
        
        pdf.ln(6)
    
    # =====================================================================
    # REKAP SERAPAN PER KEJURUAN
    # =====================================================================
    if pdf.get_y() > 150:
        pdf.add_page()
    
    pdf.set_font('Arial', 'B', 16)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('IV. REKAPITULASI PER KEJURUAN'), 0, 1, 'L')
    pdf.ln(2)
    
    rekap_col_w = [8, 70, 25, 25, 25, 25, 25, 25, 25, 25]
    rekap_headers = ['No', 'Program Kejuruan', 'Total', 'K1', 'K2', 'K3', 'K4',
                     'Instr OK', 'PK OK', 'Kompeten']
    
    pdf.set_text_color(255, 255, 255)
    _render_multicell_row(pdf, rekap_headers, rekap_col_w, LINE_H,
                          fill_color=(30, 41, 59), is_header=True)
    pdf.set_draw_color(226, 232, 240)
    
    for i, kj in enumerate(kejuruan_list, 1):
        df_kj = df[df['Kejuruan'] == kj]
        kj_q1 = len(df_kj[df_kj['Diagnosis_Awal'].str.contains("Kuadran 1", na=False)])
        kj_q2 = len(df_kj[df_kj['Diagnosis_Awal'].str.contains("Kuadran 2", na=False)])
        kj_q3 = len(df_kj[df_kj['Diagnosis_Awal'].str.contains("Kuadran 3", na=False)])
        kj_q4 = len(df_kj[df_kj['Diagnosis_Awal'].str.contains("Kuadran 4", na=False)])
        kj_inst = len(df_kj[df_kj['Status_Instruktur'] == 'Sudah Ditangani'])
        kj_pk = len(df_kj[df_kj['Status_Pengantar_Kerja'] == 'Sudah Ditangani'])
        kj_komp = len(df_kj[df_kj['Status_Kelulusan'] == 'Kompeten'])
        
        data = [str(i), str(kj), str(len(df_kj)),
                str(kj_q1), str(kj_q2), str(kj_q3), str(kj_q4),
                str(kj_inst), str(kj_pk), str(kj_komp)]
        
        fill = (248, 250, 252) if i % 2 == 0 else (255, 255, 255)
        pdf.set_text_color(15, 23, 42)
        _render_multicell_row(pdf, data, rekap_col_w, LINE_H, fill_color=fill)
    
    # Totals row
    data_total = ['', 'TOTAL', str(total), str(q1), str(q2), str(q3), str(q4),
                  str(inst_done), str(pk_done), str(kompeten)]
    pdf.set_text_color(255, 255, 255)
    _render_multicell_row(pdf, data_total, rekap_col_w, LINE_H,
                          fill_color=(51, 65, 85), is_header=True)
    
    # =====================================================================
    # AUDIT TRAIL
    # =====================================================================
    if hasattr(st, 'session_state') and st.session_state.get('audit_trail'):
        if pdf.get_y() > 150:
            pdf.add_page()
        
        pdf.set_font('Arial', 'B', 16)
        pdf.set_text_color(15, 23, 42)
        pdf.cell(0, 10, clean_pdf_text('V. JEJAK AUDIT KEPUTUSAN'), 0, 1, 'L')
        pdf.ln(2)
        
        pdf.set_font('Arial', '', 9)
        pdf.set_text_color(51, 65, 85)
        pdf.multi_cell(0, 5, clean_pdf_text(
            "Berikut adalah rekam jejak seluruh keputusan yang diambil oleh Instruktur, "
            "Pengantar Kerja, dan Pemberdayaan selama sesi ini."
        ))
        pdf.ln(3)
        
        audit_col_w = [10, 267]
        audit_headers = ['No', 'Catatan Audit']
        
        pdf.set_text_color(255, 255, 255)
        _render_multicell_row(pdf, audit_headers, audit_col_w, LINE_H,
                              fill_color=(30, 41, 59), is_header=True)
        pdf.set_draw_color(226, 232, 240)
        
        for i, log in enumerate(st.session_state.audit_trail, 1):
            if pdf.get_y() > 185:
                pdf.add_page()
                pdf.set_text_color(255, 255, 255)
                _render_multicell_row(pdf, audit_headers, audit_col_w, LINE_H,
                                      fill_color=(30, 41, 59), is_header=True)
                pdf.set_draw_color(226, 232, 240)
            
            fill = (248, 250, 252) if i % 2 == 0 else (255, 255, 255)
            pdf.set_text_color(15, 23, 42)
            _render_multicell_row(pdf, [str(i), log], audit_col_w, LINE_H, fill_color=fill)
        
        pdf.ln(4)
    
    # =====================================================================
    # SIGNATURE PAGE
    # =====================================================================
    pdf.add_page()
    pdf.set_font('Arial', 'B', 16)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('LEMBAR PENGESAHAN'), 0, 1, 'C')
    pdf.ln(5)
    
    pdf.set_fill_color(248, 250, 252)
    pdf.set_draw_color(226, 232, 240)
    y_box = pdf.get_y()
    pdf.rect(40, y_box, 217, 50, 'DF')
    pdf.set_xy(50, y_box + 5)
    pdf.set_font('Arial', '', 10)
    pdf.set_text_color(51, 65, 85)
    pdf.multi_cell(197, 6, clean_pdf_text(
        f"Laporan Indeks Kesiapan Kerja Peserta Pelatihan Vokasi ini disusun berdasarkan "
        f"analisis data tes psikometri {total} peserta pelatihan pada kejuruan {kejuruan_str}. "
        f"Analisis menggunakan kerangka 4 Kuadran yang dibangun dari teori Holland (1959), "
        f"Schmidt & Hunter (1998), dan Kristof-Brown (2005).\n\n"
        f"Distribusi kesiapan: K1 ({q1}), K2 ({q2}), K3 ({q3}), K4 ({q4}).\n"
        f"Progress intervensi: Instruktur {inst_done}/{total}, Pengantar Kerja {pk_done}/{total}.\n"
        f"Status kelulusan: {kompeten} peserta dinyatakan Kompeten."
    ))
    
    pdf.set_y(y_box + 60)
    sig_y = pdf.get_y() + 10
    sig_cols = [
        ("Mengetahui,", "Kepala Balai BPVP Surakarta"),
        ("Menyetujui,", "Kepala Seksi Penyelenggaraan"),
        ("Disusun oleh,", "Sistem ADAPTIKA v1.0"),
    ]
    col_w_sig = 85
    for i, (title, role_name) in enumerate(sig_cols):
        x = 15 + i * (col_w_sig + 10)
        pdf.set_xy(x, sig_y)
        pdf.set_font('Arial', 'B', 9)
        pdf.set_text_color(71, 85, 105)
        pdf.cell(col_w_sig, 5, clean_pdf_text(title), 0, 1, 'C')
        pdf.set_xy(x, sig_y + 5)
        pdf.set_font('Arial', '', 9)
        pdf.cell(col_w_sig, 5, clean_pdf_text(role_name), 0, 1, 'C')
        pdf.set_xy(x, sig_y + 35)
        pdf.set_font('Arial', '', 10)
        pdf.set_text_color(15, 23, 42)
        pdf.cell(col_w_sig, 5, clean_pdf_text('( ____________________ )'), 0, 1, 'C')
        pdf.set_xy(x, sig_y + 41)
        pdf.set_font('Arial', '', 8)
        pdf.set_text_color(100, 116, 139)
        pdf.cell(col_w_sig, 5, clean_pdf_text('NIP. ................................'), 0, 1, 'C')
    
    pdf.set_y(180)
    pdf.set_font('Arial', 'I', 8)
    pdf.set_text_color(148, 163, 184)
    pdf.cell(0, 5, clean_pdf_text(f'Dokumen ini digenerate otomatis oleh Sistem ADAPTIKA pada {datetime.now().strftime("%d %B %Y, %H:%M WIB")}.'), 0, 1, 'C')
    pdf.cell(0, 5, clean_pdf_text('Data bersumber dari platform SIAPKerja Kementerian Ketenagakerjaan RI.'), 0, 1, 'C')
    
    try:
        import shutil
        shutil.rmtree(tmp_dir, ignore_errors=True)
    except Exception:
        pass
    
    return pdf.output(dest='S').encode('latin1')


# =========================================================================
# CAREER PASSPORT (unchanged)
# =========================================================================

def _generate_radar_chart_passport(skor_numerik, skor_figural, tmp_dir):
    """Generate a radar/polar chart of cognitive profile for career passport."""
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    import numpy as np
    
    categories = ['Logika\nNumerik', 'Spasial\nFigural', 'Daya\nAdaptasi', 'Problem\nSolving']
    # Derive adaptive and problem-solving from the two core scores
    daya_adaptasi = min(100, int((skor_numerik + skor_figural) / 2 * 1.1))
    problem_solving = min(100, int(max(skor_numerik, skor_figural) * 0.95))
    values = [skor_numerik, skor_figural, daya_adaptasi, problem_solving]
    
    angles = np.linspace(0, 2 * np.pi, len(categories), endpoint=False).tolist()
    values_plot = values + [values[0]]
    angles += [angles[0]]
    
    fig, ax = plt.subplots(figsize=(4.5, 4.5), subplot_kw=dict(polar=True))
    
    ax.fill(angles, values_plot, color='#0d9488', alpha=0.2)
    ax.plot(angles, values_plot, color='#0d9488', linewidth=2.5, marker='o', markersize=8)
    
    # Add value labels
    for angle, val in zip(angles[:-1], values):
        ax.text(angle, val + 8, str(val), ha='center', va='center',
                fontsize=11, fontweight='bold', color='#0f766e')
    
    ax.set_xticks(angles[:-1])
    ax.set_xticklabels(categories, fontsize=9)
    ax.set_ylim(0, 110)
    ax.set_yticks([20, 40, 60, 80, 100])
    ax.set_yticklabels(['20', '40', '60', '80', '100'], fontsize=7, color='#94a3b8')
    ax.grid(color='#e2e8f0', linewidth=0.5)
    ax.spines['polar'].set_color('#e2e8f0')
    
    ax.set_title('Profil Kapasitas Kognitif', fontsize=12, fontweight='bold',
                 pad=20, color='#0f172a')
    
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'passport_radar.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def _generate_riasec_chart_passport(kode_riasec, tmp_dir):
    """Generate RIASEC personality profile bar chart."""
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    
    # RIASEC full names and colors
    riasec_map = {
        'R': ('Realistic', 'Praktis, hands-on, teknis'),
        'I': ('Investigative', 'Analitis, penyelidik, ilmiah'),
        'A': ('Artistic', 'Kreatif, ekspresif, imajinatif'),
        'S': ('Social', 'Komunikatif, kolaboratif, empati'),
        'E': ('Enterprising', 'Pemimpin, persuasif, ambisius'),
        'C': ('Conventional', 'Terstruktur, detail, akurat'),
    }
    colors_active = {
        'R': '#0d9488', 'I': '#2563eb', 'A': '#9333ea',
        'S': '#e11d48', 'E': '#ea580c', 'C': '#ca8a04'
    }
    
    labels = []
    values = []
    colors = []
    descs = []
    
    kode = str(kode_riasec).upper()
    
    for letter in ['R', 'I', 'A', 'S', 'E', 'C']:
        full_name, desc = riasec_map[letter]
        labels.append(f'{letter}\n{full_name}')
        descs.append(desc)
        if letter in kode:
            pos = kode.index(letter)
            val = max(30, 100 - pos * 20)
            values.append(val)
            colors.append(colors_active[letter])
        else:
            values.append(15)
            colors.append('#e2e8f0')
    
    fig, ax = plt.subplots(figsize=(7, 3.5))
    
    bars = ax.barh(range(len(labels)), values, color=colors, height=0.6, 
                   edgecolor='white', linewidth=1)
    
    ax.set_yticks(range(len(labels)))
    ax.set_yticklabels(labels, fontsize=8)
    ax.set_xlim(0, 110)
    ax.set_xlabel('Intensitas', fontsize=9)
    ax.set_title(f'Profil Kepribadian Holland (RIASEC): {kode}', 
                 fontsize=11, fontweight='bold', color='#0f172a')
    
    # Value labels
    for bar, val, desc in zip(bars, values, descs):
        if val > 20:
            ax.text(val - 2, bar.get_y() + bar.get_height()/2, f'{val}',
                   ha='right', va='center', fontsize=9, fontweight='bold', color='white')
            ax.text(val + 2, bar.get_y() + bar.get_height()/2, desc,
                   ha='left', va='center', fontsize=7, color='#64748b')
    
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.spines['bottom'].set_visible(False)
    ax.grid(axis='x', alpha=0.2)
    ax.invert_yaxis()
    
    fig.tight_layout()
    path = os.path.join(tmp_dir, 'passport_riasec.png')
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def _generate_gauge_chart(score, label, tmp_dir, filename):
    """Generate a compact semi-circular gauge chart for a single score."""
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    import numpy as np
    
    fig, ax = plt.subplots(figsize=(2.5, 1.8))
    
    # Background arc
    theta_bg = np.linspace(np.pi, 0, 100)
    r = 1
    ax.plot(r * np.cos(theta_bg), r * np.sin(theta_bg), color='#e2e8f0', linewidth=12, 
            solid_capstyle='round')
    
    # Score arc
    score_pct = min(100, max(0, score)) / 100
    theta_score = np.linspace(np.pi, np.pi - score_pct * np.pi, 100)
    
    if score >= 70:
        color = '#10b981'
    elif score >= 50:
        color = '#f59e0b'
    else:
        color = '#ef4444'
    
    ax.plot(r * np.cos(theta_score), r * np.sin(theta_score), color=color, linewidth=12,
            solid_capstyle='round')
    
    # Score text
    ax.text(0, 0.15, str(score), ha='center', va='center', fontsize=22,
            fontweight='bold', color=color)
    ax.text(0, -0.15, label, ha='center', va='center', fontsize=7,
            color='#64748b')
    
    ax.set_xlim(-1.3, 1.3)
    ax.set_ylim(-0.4, 1.3)
    ax.set_aspect('equal')
    ax.axis('off')
    
    fig.tight_layout(pad=0.5)
    path = os.path.join(tmp_dir, filename)
    fig.savefig(path, dpi=150, bbox_inches='tight', facecolor='white')
    plt.close(fig)
    return path


def generate_career_passport(nama_peserta, kejuruan, narasi_kekuatan, rekomendasi_ekosistem,
                             skor_numerik=0, skor_figural=0, kode_riasec='',
                             profil_riasec='', diagnosis='', status_kesiapan=''):
    """Generate a comprehensive, HR-friendly Career Passport PDF."""
    
    tmp_dir = tempfile.mkdtemp()
    
    # Generate charts
    try:
        radar_path = _generate_radar_chart_passport(skor_numerik, skor_figural, tmp_dir)
    except Exception:
        radar_path = None
    try:
        riasec_path = _generate_riasec_chart_passport(kode_riasec, tmp_dir)
    except Exception:
        riasec_path = None
    try:
        gauge_num_path = _generate_gauge_chart(skor_numerik, 'Logika Numerik', tmp_dir, 'gauge_num.png')
    except Exception:
        gauge_num_path = None
    try:
        gauge_fig_path = _generate_gauge_chart(skor_figural, 'Spasial Figural', tmp_dir, 'gauge_fig.png')
    except Exception:
        gauge_fig_path = None
    
    # Compute derived metrics
    daya_adaptasi = min(100, int((skor_numerik + skor_figural) / 2 * 1.1))
    avg_kognitif = (skor_numerik + skor_figural) // 2
    
    # Readiness level
    if 'Kuadran 1' in str(diagnosis):
        readiness_label = 'SIAP OPTIMAL'
        readiness_color = (16, 185, 129)
        readiness_bg = (209, 250, 229)
        readiness_desc = 'Peserta memiliki kapasitas kognitif dan profil kepribadian yang sangat sesuai dengan program kejuruan. Rekomendasi: Prioritas penempatan langsung ke industri.'
    elif 'Kuadran 2' in str(diagnosis):
        readiness_label = 'BERKEMBANG'
        readiness_color = (245, 158, 11)
        readiness_bg = (254, 243, 199)
        readiness_desc = 'Peserta memiliki profil kepribadian yang cocok namun memerlukan penguatan teknis. Dengan pendampingan yang tepat, peserta memiliki potensi tinggi untuk berkembang pesat.'
    elif 'Kuadran 3' in str(diagnosis):
        readiness_label = 'PERLU ARAH'
        readiness_color = (249, 115, 22)
        readiness_bg = (255, 237, 213)
        readiness_desc = 'Peserta memiliki kapasitas kognitif yang baik namun profil kepribadian menunjukkan kecocokan lebih tinggi di bidang lain. Dapat diarahkan ke posisi industri yang sesuai minat.'
    else:
        readiness_label = 'DALAM PENDAMPINGAN'
        readiness_color = (239, 68, 68)
        readiness_bg = (254, 226, 226)
        readiness_desc = 'Peserta sedang dalam proses pendampingan intensif untuk menemukan jalur karir yang paling sesuai dengan potensi uniknya.'
    
    class PassportPDF(FPDF):
        def header(self):
            if self.page_no() == 1:
                return  # Custom header on cover
            # Minimal header on subsequent pages
            self.set_fill_color(15, 118, 110)
            self.rect(0, 0, 210, 15, 'F')
            self.set_text_color(255, 255, 255)
            self.set_font('Arial', 'B', 9)
            self.text(10, 9, clean_pdf_text('ADAPTIKA CAREER PASSPORT'))
            self.set_font('Arial', '', 8)
            self.text(150, 9, clean_pdf_text(f'{nama_peserta}'))
            self.set_draw_color(6, 182, 212)
            self.set_line_width(0.5)
            self.line(0, 15, 210, 15)
            self.ln(12)
            
        def footer(self):
            self.set_y(-12)
            self.set_font('Arial', 'I', 7)
            self.set_text_color(148, 163, 184)
            self.cell(95, 8, clean_pdf_text(f'Career Passport - {nama_peserta}'), 0, 0, 'L')
            self.cell(0, 8, clean_pdf_text(f'Halaman {self.page_no()} | ADAPTIKA Enterprise'), 0, 0, 'R')
    
    pdf = PassportPDF()
    pdf.set_auto_page_break(auto=True, margin=15)
    
    # =====================================================================
    # PAGE 1: COVER
    # =====================================================================
    pdf.add_page()
    
    # Full page dark background
    pdf.set_fill_color(15, 118, 110)
    pdf.rect(0, 0, 210, 297, 'F')
    
    # Accent bar
    pdf.set_fill_color(6, 182, 212)
    pdf.rect(0, 0, 210, 5, 'F')
    
    # Title
    pdf.set_y(60)
    pdf.set_text_color(255, 255, 255)
    pdf.set_font('Arial', 'B', 28)
    pdf.cell(0, 14, clean_pdf_text('CAREER PASSPORT'), 0, 1, 'C')
    
    pdf.set_font('Arial', '', 12)
    pdf.set_text_color(153, 246, 228)  # Teal 200
    pdf.cell(0, 7, clean_pdf_text('Suplemen Kompetensi Talenta'), 0, 1, 'C')
    
    # Divider
    pdf.set_draw_color(6, 182, 212)
    pdf.set_line_width(1)
    pdf.line(70, 90, 140, 90)
    
    # Name card
    pdf.set_y(100)
    pdf.set_fill_color(13, 148, 136)  # Teal 600
    pdf.rect(30, 98, 150, 50, 'F')
    
    pdf.set_xy(30, 103)
    pdf.set_font('Arial', 'B', 20)
    pdf.set_text_color(255, 255, 255)
    pdf.cell(150, 10, clean_pdf_text(nama_peserta), 0, 1, 'C')
    
    pdf.set_x(30)
    pdf.set_font('Arial', '', 11)
    pdf.set_text_color(153, 246, 228)
    pdf.cell(150, 7, clean_pdf_text(kejuruan), 0, 1, 'C')
    
    pdf.set_x(30)
    pdf.set_font('Arial', '', 10)
    pdf.cell(150, 7, clean_pdf_text(f'Kode Kepribadian: {kode_riasec} ({profil_riasec})'), 0, 1, 'C')
    
    pdf.set_x(30)
    pdf.set_font('Arial', 'B', 10)
    pdf.set_text_color(255, 255, 255)
    pdf.cell(150, 7, clean_pdf_text(f'Status: {readiness_label}'), 0, 1, 'C')
    
    # Metadata
    pdf.set_y(165)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(153, 246, 228)
    pdf.cell(0, 6, clean_pdf_text(f'Diterbitkan: {datetime.now().strftime("%d %B %Y")}'), 0, 1, 'C')
    pdf.cell(0, 6, clean_pdf_text('Balai Pelatihan Vokasi dan Produktivitas (BPVP)'), 0, 1, 'C')
    pdf.cell(0, 6, clean_pdf_text('Kementerian Ketenagakerjaan Republik Indonesia'), 0, 1, 'C')
    
    pdf.ln(15)
    pdf.set_font('Arial', 'B', 10)
    pdf.set_text_color(255, 255, 255)
    pdf.cell(0, 6, clean_pdf_text('ADAPTIKA'), 0, 1, 'C')
    pdf.set_font('Arial', '', 8)
    pdf.set_text_color(153, 246, 228)
    pdf.cell(0, 5, clean_pdf_text('Adaptive Talent Intelligence & Kesiapan Analitik'), 0, 1, 'C')
    
    # Bottom accent
    pdf.set_fill_color(6, 182, 212)
    pdf.rect(0, 292, 210, 5, 'F')
    
    # =====================================================================
    # PAGE 2: PROFIL KOGNITIF + GAUGE
    # =====================================================================
    pdf.add_page()
    
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('1. Profil Kapasitas Kognitif'), 0, 1, 'L')
    pdf.ln(1)
    
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(71, 85, 105)
    pdf.multi_cell(0, 5, clean_pdf_text(
        "Kapasitas kognitif menunjukkan kemampuan berpikir logis dan kemampuan memproses informasi visual-spasial. "
        "Kedua dimensi ini diukur melalui tes terstandar dari platform SIAPKerja Kementerian Ketenagakerjaan RI."
    ))
    pdf.ln(3)
    
    # Gauge charts side by side
    y_gauge = pdf.get_y()
    if gauge_num_path and os.path.exists(gauge_num_path):
        pdf.image(gauge_num_path, 20, y_gauge, 55)
    if gauge_fig_path and os.path.exists(gauge_fig_path):
        pdf.image(gauge_fig_path, 80, y_gauge, 55)
    
    # Radar chart on the right
    if radar_path and os.path.exists(radar_path):
        pdf.image(radar_path, 130, y_gauge - 8, 72)
    
    pdf.set_y(y_gauge + 45)
    
    # Score interpretation table
    pdf.set_font('Arial', 'B', 10)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 8, clean_pdf_text('Interpretasi Skor:'), 0, 1, 'L')
    
    # Table
    pdf.set_fill_color(15, 118, 110)
    pdf.set_text_color(255, 255, 255)
    pdf.set_font('Arial', 'B', 8)
    pdf.cell(50, 7, clean_pdf_text('Dimensi'), 1, 0, 'C', True)
    pdf.cell(20, 7, clean_pdf_text('Skor'), 1, 0, 'C', True)
    pdf.cell(30, 7, clean_pdf_text('Kategori'), 1, 0, 'C', True)
    pdf.cell(90, 7, clean_pdf_text('Deskripsi'), 1, 1, 'L', True)
    
    def _score_category(s):
        if s >= 80: return ('Sangat Baik', 'Kemampuan di atas rata-rata. Aset berharga untuk industri.')
        elif s >= 60: return ('Baik', 'Mampu memenuhi standar industri dengan baik.')
        elif s >= 40: return ('Cukup', 'Dapat ditingkatkan dengan latihan dan pendampingan.')
        else: return ('Perlu Penguatan', 'Memerlukan scaffolding dan latihan intensif.')
    
    scores = [
        ('Logika Numerik', skor_numerik),
        ('Spasial Figural', skor_figural),
        ('Daya Adaptasi (Estimasi)', daya_adaptasi),
        ('Rata-rata Kognitif', avg_kognitif),
    ]
    
    pdf.set_text_color(15, 23, 42)
    pdf.set_font('Arial', '', 8)
    for i, (dim, val) in enumerate(scores):
        fill = i % 2 == 0
        if fill:
            pdf.set_fill_color(248, 250, 252)
        else:
            pdf.set_fill_color(255, 255, 255)
        
        cat, desc = _score_category(val)
        pdf.cell(50, 6, clean_pdf_text(dim), 1, 0, 'L', fill)
        pdf.cell(20, 6, str(val), 1, 0, 'C', fill)
        pdf.cell(30, 6, clean_pdf_text(cat), 1, 0, 'C', fill)
        pdf.cell(90, 6, clean_pdf_text(desc), 1, 1, 'L', fill)
    
    pdf.ln(5)
    
    # =====================================================================
    # RIASEC PROFILE
    # =====================================================================
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('2. Profil Kepribadian Holland (RIASEC)'), 0, 1, 'L')
    pdf.ln(1)
    
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(71, 85, 105)
    pdf.multi_cell(0, 5, clean_pdf_text(
        f"Berdasarkan teori Holland (1959), kepribadian kerja Anda tergolong dalam tipe "
        f"{profil_riasec} ({kode_riasec}). Profil ini menunjukkan gaya kerja, lingkungan ideal, "
        f"dan pendekatan problem-solving yang paling alami bagi Anda."
    ))
    pdf.ln(3)
    
    if riasec_path and os.path.exists(riasec_path):
        pdf.image(riasec_path, 10, pdf.get_y(), 190)
        pdf.ln(60)
    
    # =====================================================================
    # PAGE 3: READINESS + AI NARRATIVE
    # =====================================================================
    if pdf.get_y() > 200:
        pdf.add_page()
    
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('3. Indeks Kesiapan Kerja'), 0, 1, 'L')
    pdf.ln(1)
    
    # Readiness badge
    pdf.set_fill_color(*readiness_bg)
    pdf.set_draw_color(*readiness_color)
    y_badge = pdf.get_y()
    pdf.rect(10, y_badge, 190, 22, 'DF')
    pdf.set_xy(15, y_badge + 3)
    pdf.set_font('Arial', 'B', 12)
    pdf.set_text_color(*readiness_color)
    pdf.cell(0, 7, clean_pdf_text(f'Status Kesiapan: {readiness_label}'), 0, 1)
    pdf.set_x(15)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(51, 65, 85)
    pdf.multi_cell(180, 4.5, clean_pdf_text(readiness_desc))
    pdf.set_y(y_badge + 26)
    
    # Competency Matrix
    pdf.set_font('Arial', 'B', 10)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 8, clean_pdf_text('Matriks Kompetensi:'), 0, 1, 'L')
    
    pdf.set_fill_color(15, 118, 110)
    pdf.set_text_color(255, 255, 255)
    pdf.set_font('Arial', 'B', 8)
    pdf.cell(60, 7, clean_pdf_text('Aspek'), 1, 0, 'C', True)
    pdf.cell(30, 7, clean_pdf_text('Skor'), 1, 0, 'C', True)
    pdf.cell(40, 7, clean_pdf_text('Indikator'), 1, 0, 'C', True)
    pdf.cell(60, 7, clean_pdf_text('Keterangan HR'), 1, 1, 'L', True)
    
    competencies = [
        ('Kemampuan Berpikir Logis', skor_numerik, 
         'Dapat menganalisis dan memecahkan masalah teknis secara sistematis'),
        ('Kemampuan Visual-Spasial', skor_figural,
         'Mampu memahami blueprint, skema, dan layout dengan baik'),
        ('Kecocokan Kepribadian-Karir', 
         85 if 'Kuadran 1' in str(diagnosis) else 65 if 'Kuadran 2' in str(diagnosis) else 50,
         'Person-job fit berdasarkan profil Holland RIASEC'),
        ('Potensi Adaptabilitas Karir',
         daya_adaptasi,
         'Kemampuan beradaptasi di lingkungan kerja baru'),
    ]
    
    pdf.set_text_color(15, 23, 42)
    pdf.set_font('Arial', '', 8)
    for i, (aspek, skor, ket) in enumerate(competencies):
        fill = i % 2 == 0
        if fill:
            pdf.set_fill_color(248, 250, 252)
        else:
            pdf.set_fill_color(255, 255, 255)
        
        cat, _ = _score_category(skor)
        indicator = '★★★★★' if skor >= 80 else '★★★★☆' if skor >= 60 else '★★★☆☆' if skor >= 40 else '★★☆☆☆'
        
        pdf.cell(60, 6, clean_pdf_text(aspek), 1, 0, 'L', fill)
        pdf.cell(30, 6, clean_pdf_text(f'{skor} ({cat})'), 1, 0, 'C', fill)
        pdf.cell(40, 6, clean_pdf_text(indicator), 1, 0, 'C', fill)
        pdf.cell(60, 6, clean_pdf_text(ket), 1, 1, 'L', fill)
    
    pdf.ln(6)
    
    # =====================================================================
    # AI NARRATIVE
    # =====================================================================
    if pdf.get_y() > 220:
        pdf.add_page()
    
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('4. Profil Kekuatan & Adaptabilitas Karier'), 0, 1, 'L')
    pdf.ln(1)
    
    # AI narrative box
    pdf.set_fill_color(240, 253, 250)  # Teal 50
    pdf.set_draw_color(153, 246, 228)  # Teal 200
    y_nar = pdf.get_y()
    
    # Pre-measure height
    pdf.set_font('Arial', '', 9)
    narasi_clean = clean_pdf_text(narasi_kekuatan)
    
    # Write in a nice box
    pdf.set_xy(12, y_nar + 3)
    pdf.set_font('Arial', 'I', 8)
    pdf.set_text_color(13, 148, 136)
    pdf.cell(0, 4, clean_pdf_text('Narasi ini dirumuskan oleh AI berdasarkan data asesmen resmi:'), 0, 1)
    pdf.set_x(12)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(30, 70, 60)
    y_before = pdf.get_y()
    pdf.multi_cell(186, 5, narasi_clean)
    y_after = pdf.get_y()
    
    # Draw box around
    box_h = y_after - y_nar + 4
    pdf.rect(10, y_nar, 190, box_h, 'D')
    pdf.set_fill_color(240, 253, 250)
    
    pdf.set_y(y_after + 4)
    
    # =====================================================================
    # INDUSTRY RECOMMENDATIONS
    # =====================================================================
    if pdf.get_y() > 230:
        pdf.add_page()
    
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('5. Rekomendasi Lingkungan Kerja & Industri'), 0, 1, 'L')
    pdf.ln(1)
    
    pdf.set_fill_color(239, 246, 255)  # Blue 50
    pdf.set_draw_color(147, 197, 253)
    y_rek = pdf.get_y()
    
    pdf.set_xy(12, y_rek + 3)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(30, 58, 138)
    y_before = pdf.get_y()
    pdf.multi_cell(186, 5, clean_pdf_text(rekomendasi_ekosistem))
    y_after = pdf.get_y()
    
    box_h = y_after - y_rek + 4
    pdf.rect(10, y_rek, 190, box_h, 'D')
    
    pdf.set_y(y_after + 6)
    
    # =====================================================================
    # VALIDATION & SIGNATURE
    # =====================================================================
    if pdf.get_y() > 220:
        pdf.add_page()
    
    pdf.set_font('Arial', 'B', 14)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(0, 10, clean_pdf_text('6. Validasi & Pengesahan'), 0, 1, 'L')
    pdf.ln(1)
    
    # Validation box
    pdf.set_fill_color(240, 253, 244)
    pdf.set_draw_color(134, 239, 172)
    y_val = pdf.get_y()
    pdf.rect(10, y_val, 190, 28, 'DF')
    pdf.set_xy(15, y_val + 3)
    pdf.set_font('Arial', 'B', 10)
    pdf.set_text_color(22, 101, 52)
    pdf.cell(0, 5, clean_pdf_text('Validasi Kesiapan Kerja'), 0, 1)
    pdf.set_x(15)
    pdf.set_font('Arial', '', 9)
    pdf.set_text_color(22, 101, 52)
    pdf.multi_cell(175, 5, clean_pdf_text(
        f"Peserta atas nama {nama_peserta} telah melalui proses asesmen kesiapan kerja "
        f"yang mencakup tes kemampuan berpikir (kognitif) dan tes kepribadian RIASEC melalui platform "
        f"SIAPKerja Kementerian Ketenagakerjaan RI, serta monitoring pendampingan selama masa pelatihan "
        f"di Balai Pelatihan Vokasi dan Produktivitas. Status kesiapan: {readiness_label}."
    ))
    
    pdf.set_y(y_val + 34)
    
    # Signatures
    sig_y = pdf.get_y() + 5
    pdf.set_xy(15, sig_y)
    pdf.set_font('Arial', 'B', 9)
    pdf.set_text_color(71, 85, 105)
    pdf.cell(85, 5, clean_pdf_text('Mengetahui,'), 0, 0, 'C')
    pdf.cell(85, 5, clean_pdf_text('Disusun oleh,'), 0, 1, 'C')
    
    pdf.set_x(15)
    pdf.set_font('Arial', '', 9)
    pdf.cell(85, 5, clean_pdf_text('Kepala Balai BPVP'), 0, 0, 'C')
    pdf.cell(85, 5, clean_pdf_text('Sistem ADAPTIKA v1.0'), 0, 1, 'C')
    
    pdf.ln(18)
    
    pdf.set_x(15)
    pdf.set_font('Arial', '', 10)
    pdf.set_text_color(15, 23, 42)
    pdf.cell(85, 5, clean_pdf_text('( ____________________ )'), 0, 0, 'C')
    pdf.cell(85, 5, clean_pdf_text('( ____________________ )'), 0, 1, 'C')
    
    pdf.set_x(15)
    pdf.set_font('Arial', '', 8)
    pdf.set_text_color(100, 116, 139)
    pdf.cell(85, 5, clean_pdf_text('NIP. ..............................'), 0, 0, 'C')
    pdf.cell(85, 5, clean_pdf_text(f'Tanggal: {datetime.now().strftime("%d %B %Y")}'), 0, 1, 'C')
    
    # Footer disclaimer
    pdf.ln(8)
    pdf.set_font('Arial', 'I', 7)
    pdf.set_text_color(148, 163, 184)
    pdf.multi_cell(0, 4, clean_pdf_text(
        "Dokumen ini digenerate secara otomatis oleh Sistem ADAPTIKA Enterprise. "
        "Data asesmen bersumber dari platform SIAPKerja Kementerian Ketenagakerjaan RI. "
        "Narasi kekuatan profesional dirumuskan oleh mesin AI (Gemini) berdasarkan data asesmen resmi. "
        "Dokumen ini dapat dilampirkan sebagai suplemen kompetensi pada Curriculum Vitae (CV)."
    ), 0, 'C')
    
    try:
        import shutil
        shutil.rmtree(tmp_dir, ignore_errors=True)
    except Exception:
        pass
    
    return pdf.output(dest='S').encode('latin1')
