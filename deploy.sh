#!/bin/bash
# Smart Park — Script de déploiement
# Lance depuis le dossier du projet sur le Raspberry Pi :
#   bash deploy.sh

set -e

WEB_ROOT="/var/www/html"
PROJECT="$(cd "$(dirname "$0")" && pwd)"
LOCAL_IP=$(hostname -I | awk '{print $1}')

echo "===================================================="
echo " Smart Park — Deploiement"
echo "===================================================="

# --- Verifications ---
echo "[1/6] Verification des dependances..."

if ! command -v apache2 &>/dev/null; then
    echo "     Apache non installe. Installation..."
    sudo apt-get update -qq
    sudo apt-get install -y apache2
fi

if ! command -v php &>/dev/null; then
    echo "     PHP non installe. Installation..."
    sudo apt-get install -y php php-mysql libapache2-mod-php
fi

echo "     OK"

# --- Sauvegarde des anciens fichiers ---
echo "[2/6] Sauvegarde des fichiers existants (suffixe _old)..."

backup() {
    local name="$1"
    local target="$WEB_ROOT/$name"
    local ext="${name##*.}"
    local bak

    # Dossier ou fichier sans extension -> name_old
    # Fichier avec extension         -> name_old.ext
    if [ "$name" = "$ext" ]; then
        bak="$WEB_ROOT/${name}_old"
    else
        bak="$WEB_ROOT/${name%.*}_old.${ext}"
    fi

    if [ -e "$target" ]; then
        sudo rm -rf "$bak"
        sudo mv "$target" "$bak"
        echo "     $name  =>  ${bak##*/}"
    fi
}

# Dossiers
backup "api"
backup "assets"
backup "config"
backup "includes"
backup "models"

# Fichiers PHP
backup "index.php"
backup "inscription.php"
backup "badges.php"
backup "logs.php"

# Fichier de reset (on ne sauvegarde pas, on garde l'existant s'il y en a un)
if [ ! -f "$WEB_ROOT/reset_ordre.txt" ]; then
    sudo cp "$PROJECT/reset_ordre.txt" "$WEB_ROOT/"
fi

echo "     OK"

# --- Copie des nouveaux fichiers ---
echo "[3/6] Copie des nouveaux fichiers vers $WEB_ROOT..."

sudo cp -r "$PROJECT/api"      "$WEB_ROOT/"
sudo cp -r "$PROJECT/assets"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/config"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/includes" "$WEB_ROOT/"
sudo cp -r "$PROJECT/models"   "$WEB_ROOT/"

sudo cp "$PROJECT/index.php"       "$WEB_ROOT/"
sudo cp "$PROJECT/inscription.php" "$WEB_ROOT/"
sudo cp "$PROJECT/badges.php"      "$WEB_ROOT/"
sudo cp "$PROJECT/logs.php"        "$WEB_ROOT/"

echo "     OK"

# --- Permissions ---
echo "[4/6] Application des permissions..."

sudo chown -R www-data:www-data "$WEB_ROOT"
sudo find "$WEB_ROOT" -type d -exec chmod 755 {} \;
sudo find "$WEB_ROOT" -type f -exec chmod 644 {} \;
sudo chmod 664 "$WEB_ROOT/reset_ordre.txt"

echo "     OK"

# --- Apache ---
echo "[5/6] Redemarrage d'Apache..."
sudo systemctl restart apache2
echo "     OK"

# --- Test ---
echo "[6/6] Test de connectivite..."
sleep 1
HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/index.php 2>/dev/null || echo "000")
if [ "$HTTP" = "200" ]; then
    echo "     HTTP $HTTP — Site operationnel"
else
    echo "     HTTP $HTTP — Verifiez Apache et PHP"
fi

echo ""
echo "===================================================="
echo " Termine !"
echo " Dashboard : http://$LOCAL_IP/index.php"
echo " API ESP32 : http://$LOCAL_IP/api/check_uid.php"
echo ""
echo " Anciens fichiers gardes avec le suffixe _old :"
echo "   api_old/  assets_old/  config_old/  models_old/"
echo "   index_old.php  badges_old.php  logs_old.php  etc."
echo "===================================================="
