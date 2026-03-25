#!/bin/bash
# ============================================
# Auto-Push Smart Park vers GitHub
# Synchronise ProjetWeb -> smart-park puis push
# ============================================

PROJET_DIR="/home/pi/Downloads/ProjetWeb"
REPO_DIR="/home/pi/Downloads/ProjetWeb/smart-park"
LOG_FILE="/home/pi/auto-push.log"

# Fichiers/dossiers à exclure de la synchronisation
EXCLUDES=(
    "--exclude=smart-park"
    "--exclude=.git"
    "--exclude=ProjetWeb_Update.zip"
    "--exclude=auto-push.sh"
    "--exclude=*.log"
)

# 1. Synchroniser ProjetWeb -> smart-park
rsync -av --delete "${EXCLUDES[@]}" "$PROJET_DIR/" "$REPO_DIR/" >> "$LOG_FILE" 2>&1

# 2. Aller dans le repo
cd "$REPO_DIR" || exit 1

# 3. Vérifier s'il y a des changements
if [[ -n $(git status --porcelain) ]]; then
    TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
    git add -A >> "$LOG_FILE" 2>&1
    git commit -m "Auto-sync: $TIMESTAMP" >> "$LOG_FILE" 2>&1
    git push origin main >> "$LOG_FILE" 2>&1
    echo "[$TIMESTAMP] Push effectué." >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Aucun changement." >> "$LOG_FILE"
fi
