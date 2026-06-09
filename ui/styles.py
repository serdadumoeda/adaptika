import streamlit as st

def inject_custom_css():
    CUSTOM_CSS = """
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

    html, body, .stApp {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Base App Colors adapting to Streamlit Theme */
    .stApp {
        background-color: var(--background-color);
        color: var(--text-color);
    }

    /* Prevent material icon font fallback text from overflowing in offline/sandboxed environments */
    [data-testid*="Icon"] {
        max-width: 24px;
        overflow: hidden;
        white-space: nowrap;
    }

    /* Sidebar Custom Styling (Always Dark Slate for Premium Look) */
    section[data-testid="stSidebar"] {
        background-color: #0f172a !important; /* Slate 900 */
        border-right: 1px solid #1e293b;
    }
    section[data-testid="stSidebar"] hr {
        border-color: #334155 !important;
    }
    section[data-testid="stSidebar"] .stMarkdown p, 
    section[data-testid="stSidebar"] label,
    section[data-testid="stSidebar"] .stCaptionContainer {
        color: #cbd5e1 !important;
    }
    section[data-testid="stSidebar"] h1, 
    section[data-testid="stSidebar"] h2, 
    section[data-testid="stSidebar"] h3 {
        color: #ffffff !important;
    }
    section[data-testid="stSidebar"] .stMetric {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        padding: 12px 16px !important;
        border-radius: 8px !important;
        margin-bottom: 10px !important;
    }
    section[data-testid="stSidebar"] div[data-testid="stMetricValue"] {
        color: #ffffff !important;
        font-size: 24px !important;
    }
    section[data-testid="stSidebar"] [data-testid="stMetricLabel"],
    section[data-testid="stSidebar"] [data-testid="stMetricLabel"] * {
        color: #cbd5e1 !important;
    }
    section[data-testid="stSidebar"] div[data-testid="stNotification"] {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
    }
    section[data-testid="stSidebar"] div[data-testid="stNotification"] * {
        color: #cbd5e1 !important;
    }
    section[data-testid="stSidebar"] button[kind="secondary"] {
        background-color: #1e293b !important;
        color: #ffffff !important;
        border: 1px solid #334155 !important;
        transition: all 0.2s ease-in-out !important;
    }
    section[data-testid="stSidebar"] button[kind="secondary"] * {
        color: #ffffff !important;
    }
    section[data-testid="stSidebar"] button[kind="secondary"]:hover {
        border-color: #3b82f6 !important;
        background-color: #334155 !important;
    }
    section[data-testid="stSidebar"] button[kind="secondary"]:hover * {
        color: #3b82f6 !important;
    }
    section[data-testid="stSidebar"] button[kind="primary"] {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        color: #ffffff !important;
        border: none !important;
    }
    section[data-testid="stSidebar"] button[kind="primary"] * {
        color: #ffffff !important;
    }

    /* Metric Card Custom Styling (Main Dashboard - Theme Adaptive) */
    div[data-testid="metric-container"] {
        background-color: var(--secondary-background-color) !important;
        border: 1px solid rgba(128, 128, 128, 0.2) !important;
        border-radius: 12px !important;
        padding: 16px 20px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    div[data-testid="metric-container"]:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.04) !important;
        border-color: var(--primary-color) !important;
    }
    div[data-testid="stMetricValue"] {
        font-size: 32px !important;
        font-weight: 700 !important;
        color: var(--text-color) !important;
    }
    div[data-testid="stMetricLabel"] {
        font-size: 13px !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-color) !important;
        opacity: 0.7;
    }

    /* Form Custom Styling (Theme Adaptive) */
    div[data-testid="stForm"] {
        background-color: var(--secondary-background-color) !important;
        border: 1px solid rgba(128, 128, 128, 0.2) !important;
        border-radius: 16px !important;
        padding: 32px !important;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.03) !important;
    }

    /* Buttons */
    button[kind="primary"] {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        color: white !important;
        border: none !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 10px 24px !important;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2) !important;
        transition: all 0.2s ease-in-out !important;
    }
    button[kind="primary"]:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.35) !important;
        transform: translateY(-1px);
    }
    button[kind="secondary"] {
        border: 1px solid rgba(128, 128, 128, 0.2) !important;
        border-radius: 8px !important;
        font-weight: 500 !important;
        color: var(--text-color) !important;
        background-color: var(--background-color) !important;
        transition: all 0.2s ease-in-out !important;
    }
    button[kind="secondary"]:hover {
        border-color: var(--primary-color) !important;
        color: var(--primary-color) !important;
        background-color: var(--secondary-background-color) !important;
    }

    /* Custom Card Wrapper (Theme Adaptive) */
    .custom-card {
        background-color: var(--secondary-background-color);
        border: 1px solid rgba(128, 128, 128, 0.2);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .custom-card-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 16px;
        border-bottom: 2px solid rgba(128, 128, 128, 0.2);
        padding-bottom: 8px;
    }

    /* Tabs custom styles */
    div[data-testid="stTabBar"] button {
        font-weight: 600 !important;
        font-size: 15px !important;
        color: var(--text-color) !important;
        opacity: 0.6;
    }
    div[data-testid="stTabBar"] button[aria-selected="true"] {
        color: var(--primary-color) !important;
        opacity: 1;
    }

    /* Table and dataframe borders */
    div[data-testid="stDataFrame"] {
        border: 1px solid rgba(128, 128, 128, 0.2) !important;
        border-radius: 8px !important;
        overflow: hidden;
    }

    /* Custom Alert Badges */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-danger { background-color: #fee2e2; color: #991b1b; }
    .badge-warning { background-color: #fef3c7; color: #92400e; }
    .badge-success { background-color: #dcfce7; color: #166534; }
    .badge-info { background-color: #e0f2fe; color: #075985; }
    </style>
    """
    st.markdown(CUSTOM_CSS, unsafe_allow_html=True)
