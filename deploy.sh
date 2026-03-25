#!/bin/bash
# Smart Park — Script de deploiement
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

if ! command -v mysql &>/dev/null; then
    echo "     MariaDB non installe. Installation..."
    sudo apt-get install -y mariadb-server
fi

echo "     OK"

# --- Base de donnees ---
echo "[2/6] Initialisation de la base de donnees..."

mysql -u root -proot << 'SQL'
CREATE DATABASE IF NOT EXISTS smart_park
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin';
ALTER USER 'admin'@'localhost' IDENTIFIED BY 'admin';
GRANT ALL PRIVILEGES ON smart_park.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;

USE smart_park;

CREATE TABLE IF NOT EXISTS users (
    id            INT          NOT NULL AUTO_INCREMENT,
    nom           VARCHAR(100) NOT NULL,
    prenom        VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS badges (
    id         INT          NOT NULL AUTO_INCREMENT,
    user_id    INT          DEFAULT NULL,
    tag_uid    VARCHAR(50)  NOT NULL,
    nom        VARCHAR(100) NOT NULL DEFAULT 'Inconnu',
    autorise   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_uid (tag_uid),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (password is also 'admin')
INSERT IGNORE INTO users (id, nom, prenom, email, password_hash, role) 
VALUES (1, 'Admin', 'Super', 'admin@smartpark.local', '\$2y\$10\$YJ6k8hVZJm5fN3wQv2n5IuKq1c9P7zL4E8M6X0H2A3B4D5F6G7I8K', 'admin');

CREATE TABLE IF NOT EXISTS places (
    id_place   INT         NOT NULL,
    etat       ENUM('libre','occupee','panne','reservee') NOT NULL DEFAULT 'libre',
    uid_actuel VARCHAR(50) DEFAULT NULL,
    reserve_par INT DEFAULT NULL,
    reserve_jusqu_a TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_place),
    FOREIGN KEY (reserve_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
    id         INT         NOT NULL AUTO_INCREMENT,
    date_heure TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tag_id     VARCHAR(50) NOT NULL,
    action     VARCHAR(50) NOT NULL,
    slot       INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tag  (tag_id),
    KEY idx_date (date_heure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO places (id_place, etat) VALUES (1,'libre'),(2,'libre'),(3,'libre');

CREATE TABLE IF NOT EXISTS settings (
    cle VARCHAR(50) NOT NULL,
    val VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (cle, val) VALUES
    ('parking_open',     '07:00'),
    ('parking_close',    '19:00'),
    ('schedule_enabled', '1');
SQL

echo "     OK"

# --- Nettoyage des anciens fichiers ---
echo "[3/6] Suppression des anciens fichiers..."

for item in api assets config includes models \
            index.php inscription.php badges.php logs.php setup.php \
            check_uid.php api_rfid.php clear_reset.php \
            login.php logout.php register.php; do
    sudo rm -rf "$WEB_ROOT/$item"
done

# Fichiers de flag inscription (conserver si existants pour ne pas perdre l'état)
# → recréés proprement si absents

echo "     OK"

# --- Copie des nouveaux fichiers ---
echo "[4/6] Copie des fichiers vers $WEB_ROOT..."

sudo cp -r "$PROJECT/api"      "$WEB_ROOT/"
sudo cp -r "$PROJECT/assets"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/config"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/includes" "$WEB_ROOT/"
sudo cp -r "$PROJECT/models"   "$WEB_ROOT/"

sudo cp "$PROJECT/index.php"       "$WEB_ROOT/"
sudo cp "$PROJECT/inscription.php" "$WEB_ROOT/"
sudo cp "$PROJECT/badges.php"      "$WEB_ROOT/"
sudo cp "$PROJECT/logs.php"        "$WEB_ROOT/"
sudo cp "$PROJECT/setup.php"       "$WEB_ROOT/"
sudo cp "$PROJECT/check_uid.php"   "$WEB_ROOT/"
sudo cp "$PROJECT/api_rfid.php"    "$WEB_ROOT/"
sudo cp "$PROJECT/clear_reset.php" "$WEB_ROOT/"
sudo cp "$PROJECT/login.php"       "$WEB_ROOT/"
sudo cp "$PROJECT/logout.php"      "$WEB_ROOT/"
sudo cp "$PROJECT/register.php"    "$WEB_ROOT/"
sudo cp "$PROJECT/home.php"        "$WEB_ROOT/"

# Fichiers d'état : ne pas écraser s'ils existent
if [ ! -f "$WEB_ROOT/reset_ordre.txt" ]; then
    sudo cp "$PROJECT/reset_ordre.txt" "$WEB_ROOT/"
fi
if [ ! -f "$WEB_ROOT/inscription_mode.txt" ]; then
    echo -n "0" | sudo tee "$WEB_ROOT/inscription_mode.txt" > /dev/null
fi
if [ ! -f "$WEB_ROOT/inscription_uid.txt" ]; then
    echo -n "" | sudo tee "$WEB_ROOT/inscription_uid.txt" > /dev/null
fi

echo "     OK"

# --- Permissions ---
echo "[5/6] Application des permissions..."

sudo chown -R www-data:www-data "$WEB_ROOT"
sudo find "$WEB_ROOT" -type d -exec chmod 755 {} \;
sudo find "$WEB_ROOT" -type f -exec chmod 644 {} \;
sudo chmod 664 "$WEB_ROOT/reset_ordre.txt"
sudo chmod 664 "$WEB_ROOT/inscription_mode.txt"
sudo chmod 664 "$WEB_ROOT/inscription_uid.txt"

echo "     OK"

# --- Apache ---
echo "[6/6] Redemarrage d'Apache..."
sudo systemctl restart apache2
echo "     OK"

sleep 1
HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/index.php 2>/dev/null || echo "000")

echo ""
echo "===================================================="
echo " Termine !  HTTP $HTTP"
echo " Dashboard  : http://$LOCAL_IP/index.php"
echo " Verification : http://$LOCAL_IP/setup.php"
echo "===================================================="
