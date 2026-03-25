#!/bin/bash
# ============================================
# Auto-Sync Smart Park — Pull + Push
# Synchronise les 2 postes via GitHub
# ============================================

PROJET_DIR="/home/pi/Downloads/ProjetWeb"
REPO_DIR="/home/pi/Downloads/ProjetWeb/smart-park"
LOG_FILE="/home/pi/auto-push.log"

EXCLUDES=(
    "--exclude=smart-park"
    "--exclude=.git"
    "--exclude=ProjetWeb_Update.zip"
    "--exclude=auto-push.sh"
    "--exclude=auto-sync.sh"
    "--exclude=*.log"
)

cd "$REPO_DIR" || exit 1

# ── 1. PULL : récupérer les changements de l'autre poste ──
git pull --rebase origin main >> "$LOG_FILE" 2>&1

# Copier les fichiers mis à jour depuis le repo vers ProjetWeb
rsync -av --delete \
    --exclude='.git' \
    --exclude='auto-push.sh' \
    --exclude='auto-sync.sh' \
    "$REPO_DIR/" "$PROJET_DIR/" >> "$LOG_FILE" 2>&1

# ── 2. SYNC : copier les fichiers modifiés localement vers le repo ──
rsync -av --delete "${EXCLUDES[@]}" "$PROJET_DIR/" "$REPO_DIR/" >> "$LOG_FILE" 2>&1

# ── 3. PUSH : envoyer les changements sur GitHub ──
cd "$REPO_DIR" || exit 1

if [[ -n $(git status --porcelain) ]]; then
    TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
    git add -A >> "$LOG_FILE" 2>&1
    git commit -m "Auto-sync: $TIMESTAMP" >> "$LOG_FILE" 2>&1
    git push origin main >> "$LOG_FILE" 2>&1
    echo "[$TIMESTAMP] ✅ Push effectué." >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] — Aucun changement." >> "$LOG_FILE"
fi
