@echo off
REM Chay ngrok mo port 8000 (Laravel). Doi 8000 neu app chay port khac.
REM Can dat ngrok.exe vao thu muc nay hoac them ngrok vao PATH.

if exist "%~dp0ngrok.exe" (
    "%~dp0ngrok.exe" http 8000
) else (
    ngrok http 8000
)
