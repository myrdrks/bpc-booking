#!/bin/bash

# Schnellstart-Skript für Raumbuchungssystem mit Docker
# Dieses Skript richtet alles automatisch ein

set -e

echo "================================================"
echo "  Raumbuchungssystem - Docker Schnellstart"
echo "================================================"
echo ""

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker läuft nicht. Bitte starte Docker Desktop.${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Docker läuft"

# Prüfe ob MySQL läuft
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}⚠${NC} MySQL-Client nicht gefunden. Installation wird fortgesetzt..."
else
    echo -e "${GREEN}✓${NC} MySQL-Client gefunden"
fi

# Verzeichnisse erstellen
echo ""
echo "→ Erstelle erforderliche Verzeichnisse..."
mkdir -p logs uploads credentials
chmod -R 755 logs uploads credentials
echo -e "${GREEN}✓${NC} Verzeichnisse erstellt"

# Datenbank einrichten
echo ""
echo "→ Datenbank-Setup..."
if command -v mysql &> /dev/null; then
    echo "   Versuche Verbindung zu MySQL herzustellen..."
    
    # Versuche verschiedene Verbindungsmethoden
    MYSQL_CONNECTED=false
    
    # Versuch 1: TCP-Verbindung zu localhost:3306
    if mysql -h 127.0.0.1 -u root -p -e "SELECT 1;" > /dev/null 2>&1; then
        MYSQL_HOST="-h 127.0.0.1"
        MYSQL_CONNECTED=true
        echo -e "${GREEN}✓${NC} Verbindung über TCP (127.0.0.1:3306)"
    # Versuch 2: Socket-Verbindung
    elif mysql -u root -p -e "SELECT 1;" > /dev/null 2>&1; then
        MYSQL_HOST=""
        MYSQL_CONNECTED=true
        echo -e "${GREEN}✓${NC} Verbindung über Socket"
    fi
    
    if [ "$MYSQL_CONNECTED" = true ]; then
        echo "   Erstelle Datenbank 'buchung'..."
        mysql $MYSQL_HOST -u root -p <<EOF
CREATE DATABASE IF NOT EXISTS buchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Datenbank 'buchung' erstellt"
            
            echo "   Importiere Schema..."
            mysql $MYSQL_HOST -u root -p buchung < database/schema.sql 2>&1 | grep -v "ERROR 1062"
            
            # Prüfe ob kritische Fehler auftraten (ignoriere Duplicate Entry Fehler)
            if mysql $MYSQL_HOST -u root -p buchung -e "SHOW TABLES;" > /dev/null 2>&1; then
                echo -e "${GREEN}✓${NC} Schema importiert (oder bereits vorhanden)"
            else
                echo -e "${YELLOW}⚠${NC} Schema-Import hatte Probleme"
            fi
        else
            echo -e "${RED}✗${NC} Datenbank konnte nicht erstellt werden"
        fi
    else
        echo -e "${YELLOW}⚠${NC} Konnte keine Verbindung zu MySQL herstellen"
        echo ""
        echo "   Mögliche Lösungen:"
        echo "   1. Starte MySQL: brew services start mysql"
        echo "   2. Oder erstelle die Datenbank manuell:"
        echo "      mysql -h 127.0.0.1 -u root -p -e \"CREATE DATABASE buchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
        echo "      mysql -h 127.0.0.1 -u root -p buchung < database/schema.sql"
        echo ""
        read -p "Drücke Enter, wenn die Datenbank erstellt wurde, oder Ctrl+C zum Abbrechen..."
    fi
else
    echo -e "${YELLOW}⚠${NC} MySQL-Client nicht verfügbar. Bitte erstelle die Datenbank manuell:"
    echo ""
    echo "   CREATE DATABASE buchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "   USE buchung;"
    echo "   SOURCE database/schema.sql;"
    echo ""
    read -p "Drücke Enter, wenn die Datenbank erstellt wurde..."
fi

# Docker Compose starten
echo ""
echo "→ Starte Docker Container..."
docker compose up -d

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Container gestartet"
else
    echo -e "${RED}✗${NC} Container-Start fehlgeschlagen"
    exit 1
fi

# Warte kurz, damit Container hochfahren
echo ""
echo "→ Warte auf Container-Initialisierung..."
sleep 5

# Prüfe ob Container läuft
if docker ps | grep -q bpc-buchung-web; then
    echo -e "${GREEN}✓${NC} Container läuft"
else
    echo -e "${RED}✗${NC} Container läuft nicht"
    echo "   Prüfe die Logs mit: docker compose logs"
    exit 1
fi

# Composer-Abhängigkeiten installieren
echo ""
echo "→ Installiere Composer-Abhängigkeiten..."
docker compose run --rm composer install --ignore-platform-reqs

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Abhängigkeiten installiert"
else
    echo -e "${YELLOW}⚠${NC} Composer-Installation hatte Probleme (kann bei fehlenden Google/PHPMailer Paketen normal sein)"
fi

# Erfolg
echo ""
echo "================================================"
echo -e "${GREEN}✓ Installation erfolgreich abgeschlossen!${NC}"
echo "================================================"
echo ""
echo "📋 Nächste Schritte:"
echo ""
echo "1. Öffne im Browser:"
echo "   → Raumübersicht:  http://localhost:8080/raeume.php"
echo "   → CLUB27:         http://localhost:8080/club27.php"
echo "   → Tagungsraum:    http://localhost:8080/tagungsraum.php"
echo "   → Club-Lounge:    http://localhost:8080/club-lounge.php"
echo "   → Admin-Panel:    http://localhost:8080/admin.php"
echo ""
echo "2. Admin-Login:"
echo "   Benutzername: admin"
echo "   Passwort:     admin123"
echo "   ${YELLOW}⚠ WICHTIG: Passwort nach erstem Login ändern!${NC}"
echo ""
echo "3. Container-Verwaltung:"
echo "   → Logs anzeigen:    docker compose logs -f"
echo "   → Container stoppen: docker compose down"
echo "   → Neustart:         docker compose restart"
echo ""
echo "4. Konfiguration anpassen:"
echo "   → Datenbank:        config.php"
echo "   → Google Calendar:  config.php (API-Keys eintragen)"
echo "   → E-Mail (SMTP):    config.php"
echo ""
echo "================================================"
echo ""
