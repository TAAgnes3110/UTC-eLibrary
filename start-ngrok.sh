#!/usr/bin/env bash
# Chay ngrok mo port 8000 (Laravel). Doi 8000 neu app chay port khac.
# Git Bash: ./start-ngrok.sh
# Neu co ngrok.exe trong thu muc project: ./ngrok.exe http 8000

if [ -f "./ngrok.exe" ]; then
    ./ngrok.exe http 8000
elif command -v ngrok &>/dev/null; then
    ngrok http 8000
else
    echo "Khong tim thay ngrok. Hay:"
    echo "  1. Tai ngrok tai https://ngrok.com/download"
    echo "  2. Dat ngrok.exe vao thu muc: $(pwd)"
    echo "  Hoac dung duong dan day du, vd: /c/ngrok/ngrok.exe http 8000"
    exit 1
fi
