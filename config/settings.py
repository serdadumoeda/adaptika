import streamlit as st
import os
from openai import OpenAI

class Settings:
    """Manajemen konfigurasi dan secrets terpusat (Singleton)"""
    
    @staticmethod
    def get_api_key(service="GROQ"):
        """Mengambil API Key secara aman dari session_state atau st.secrets"""
        try:
            if service == "GROQ":
                # Check session state first
                if "groq_api_key" in st.session_state and st.session_state.groq_api_key:
                    return st.session_state.groq_api_key
                # Fallback to secrets
                return st.secrets.get("API_KEY", None)
            return None
        except Exception as e:
            return None

    @staticmethod
    def is_production():
        return st.secrets.get("ENVIRONMENT", "development") == "production"

def get_groq_client():
    api_key = Settings.get_api_key("GROQ")
    if not api_key:
        return None
    try:
        return OpenAI(
            api_key=api_key,
            base_url="https://api.groq.com/openai/v1"
        )
    except Exception as e:
        st.error(f"Gagal inisialisasi Groq client: {e}")
        return None
