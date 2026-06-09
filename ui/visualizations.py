import plotly.express as px
import pandas as pd

def create_progress_instruktur_chart(df: pd.DataFrame):
    df_inst_count = df['Status_Instruktur'].value_counts().reset_index()
    df_inst_count.columns = ['Status', 'Jumlah']
    fig = px.bar(
        df_inst_count, 
        x='Status', 
        y='Jumlah', 
        color='Status',
        color_discrete_map={'Belum Ditangani': '#f87171', 'Sudah Ditangani': '#34d399'},
        title="Progress Instruktur"
    )
    fig.update_layout(
        showlegend=False, 
        xaxis_title="", 
        yaxis_title="", 
        margin=dict(l=10, r=10, t=40, b=10),
        height=260,
        paper_bgcolor='rgba(0,0,0,0)',
        plot_bgcolor='rgba(0,0,0,0)',
        font_family="Plus Jakarta Sans"
    )
    fig.update_yaxes(showgrid=True, gridwidth=1, gridcolor='#f1f5f9')
    return fig

def create_progress_pk_chart(df: pd.DataFrame):
    df_pk_count = df['Status_Pengantar_Kerja'].value_counts().reset_index()
    df_pk_count.columns = ['Status', 'Jumlah']
    fig = px.bar(
        df_pk_count, 
        x='Status', 
        y='Jumlah', 
        color='Status',
        color_discrete_map={'Belum Ditangani': '#f87171', 'Sudah Ditangani': '#34d399'},
        title="Progress Pengantar Kerja"
    )
    fig.update_layout(
        showlegend=False, 
        xaxis_title="", 
        yaxis_title="", 
        margin=dict(l=10, r=10, t=40, b=10),
        height=260,
        paper_bgcolor='rgba(0,0,0,0)',
        plot_bgcolor='rgba(0,0,0,0)',
        font_family="Plus Jakarta Sans"
    )
    fig.update_yaxes(showgrid=True, gridwidth=1, gridcolor='#f1f5f9')
    return fig

def create_trend_kejuruan_chart(df: pd.DataFrame):
    df_agregasi = df.groupby(['Kejuruan', 'Diagnosis_Awal']).size().reset_index(name='Jumlah')
    fig = px.bar(
        df_agregasi, 
        x="Kejuruan", 
        y="Jumlah", 
        color="Diagnosis_Awal", 
        barmode="stack",
        color_discrete_sequence=px.colors.qualitative.Pastel
    )
    fig.update_layout(
        xaxis_title="Program Kejuruan",
        yaxis_title="Jumlah Peserta",
        paper_bgcolor='rgba(0,0,0,0)',
        plot_bgcolor='rgba(0,0,0,0)',
        margin=dict(l=10, r=10, t=20, b=10),
        legend=dict(title="Status Kesiapan", yanchor="top", y=0.99, xanchor="right", x=0.99),
        font_family="Plus Jakarta Sans"
    )
    return fig

def create_scatter_kognitif_chart(df: pd.DataFrame):
    fig = px.scatter(
        df,
        x="Skor_Logika_Numerik",
        y="Skor_Spasial_Figural",
        color="Diagnosis_Awal",
        hover_name="Nama",
        labels={
            "Skor_Logika_Numerik": "Kapasitas Logika Numerik", 
            "Skor_Spasial_Figural": "Kapasitas Spasial Figural",
            "Diagnosis_Awal": "Status Kesiapan"
        },
        color_discrete_sequence=px.colors.qualitative.Safe
    )
    fig.update_layout(
        xaxis=dict(range=[-5, 105], gridcolor="rgba(128, 128, 128, 0.2)"), 
        yaxis=dict(range=[-5, 105], gridcolor="rgba(128, 128, 128, 0.2)"),
        paper_bgcolor='rgba(0,0,0,0)',
        plot_bgcolor='rgba(0,0,0,0)',
        margin=dict(l=10, r=10, t=40, b=10),
        legend=dict(yanchor="top", y=0.99, xanchor="left", x=0.01),
        font_family="Plus Jakarta Sans"
    )
    return fig

def create_radar_kognitif_chart(df_radar: pd.DataFrame):
    fig = px.line_polar(df_radar, r='Nilai', theta='Kategori', line_close=True, range_r=[0, 100])
    fig.update_traces(
        fill='toself', 
        fillcolor='rgba(59, 130, 246, 0.25)', 
        line_color='#2563eb', 
        mode='lines+markers+text', 
        text=df_radar['Nilai'], 
        textposition='bottom center',
        textfont=dict(color='white')
    )
    fig.update_layout(
        template="plotly_dark",
        polar=dict(
            bgcolor='rgba(0,0,0,0)',
            radialaxis=dict(
                visible=True, 
                range=[0, 100], 
                gridcolor="rgba(255, 255, 255, 0.2)",
                tickfont=dict(color='white')
            ),
            angularaxis=dict(
                gridcolor="rgba(255, 255, 255, 0.2)",
                tickfont=dict(color='white')
            )
        ), 
        showlegend=False, 
        margin=dict(t=40, b=40, l=80, r=80),
        font_family="Plus Jakarta Sans",
        paper_bgcolor='rgba(0,0,0,0)',
        plot_bgcolor='rgba(0,0,0,0)'
    )
    return fig
