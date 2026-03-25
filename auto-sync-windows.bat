@echo off
:: ============================================
:: Auto-Sync Smart Park — Windows
:: Tire les changements du Raspberry Pi
:: et pousse les modifications locales
:: ============================================

set REPO_DIR=%USERPROFILE%\Desktop\smart-park
set LOG_FILE=%USERPROFILE%\auto-sync.log

cd /d "%REPO_DIR%"
if errorlevel 1 exit /b

:: Pull les changements
git pull --rebase origin main >> "%LOG_FILE%" 2>&1

:: Vérifier s'il y a des changements locaux
git status --porcelain > "%TEMP%\git_status.tmp"
set /p STATUS=<"%TEMP%\git_status.tmp"
del "%TEMP%\git_status.tmp"

if not "%STATUS%"=="" (
    echo [%DATE% %TIME%] Changements detectes, push en cours... >> "%LOG_FILE%"
    git add -A >> "%LOG_FILE%" 2>&1
    git commit -m "Auto-sync Windows: %DATE% %TIME%" >> "%LOG_FILE%" 2>&1
    git push origin main >> "%LOG_FILE%" 2>&1
    echo [%DATE% %TIME%] Push effectue. >> "%LOG_FILE%"
) else (
    echo [%DATE% %TIME%] Aucun changement. >> "%LOG_FILE%"
)
