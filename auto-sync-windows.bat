@echo off
cd /d "C:\Users\achra\OneDrive\Escritorio\ProjetWeb\ProjetWeb"

echo [%date% %time%] Début de la synchronisation...
git pull origin main

:: Vérifier s'il y a des modifications à pousser
git add .
git diff-index --quiet HEAD
if errorlevel 1 (
    git commit -m "Auto-sync Windows: %date% %time%"
    git push origin main
    echo Modifications poussées vers GitHub.
) else (
    echo Aucune modification locale à pousser.
)
