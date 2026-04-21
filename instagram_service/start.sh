#!/bin/bash
# SemreMETA Instagram Service - Başlatma scripti
# CloudPanel'de Supervisor veya screen ile çalıştırın

cd "$(dirname "$0")"

# Virtual environment yoksa oluştur
if [ ! -d "venv" ]; then
    echo "Virtual environment oluşturuluyor..."
    python3 -m venv venv
    source venv/bin/activate
    pip install -r requirements.txt
else
    source venv/bin/activate
fi

echo "Instagram Service başlatılıyor: http://127.0.0.1:8765"
IG_SERVICE_API_KEY=semremeta_ig_2024 uvicorn main:app --host 127.0.0.1 --port 8765 --workers 1
