#!/bin/bash
# Deployment Script für IONOS

echo "=== Deployment Vorbereitung ==="

# 1. Prüfe ob alle notwendigen Dateien vorhanden sind
echo "Prüfe Dateien..."
required_files=("index.php" "admin.php" "config.php" "database/schema.sql")
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Fehler: $file nicht gefunden!"
        exit 1
    fi
done
echo "✓ Alle Dateien vorhanden"

# 2. Erstelle .gitkeep Dateien für leere Verzeichnisse
echo "Erstelle .gitkeep Dateien..."
touch credentials/.gitkeep
touch logs/.gitkeep
touch uploads/.gitkeep
echo "✓ .gitkeep Dateien erstellt"

# 3. Kopiere Production .htaccess
echo "Kopiere .htaccess..."
if [ -f ".htaccess-production" ]; then
    cp .htaccess-production .htaccess
    echo "✓ .htaccess kopiert"
else
    echo "⚠️  .htaccess-production nicht gefunden"
fi

# 4. Erstelle Deployment-Paket (ohne sensible Daten)
echo "Erstelle Deployment-Paket..."
tar -czf buchung-deploy.tar.gz \
    --exclude='credentials/*.json' \
    --exclude='logs/*.log' \
    --exclude='docker-compose.yml' \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='*.md' \
    --exclude='test-*.php' \
    --exclude='debug-*.php' \
    --exclude='buchung-deploy.tar.gz' \
    .

echo "✓ Deployment-Paket erstellt: buchung-deploy.tar.gz"

# 5. Checkliste anzeigen
echo ""
echo "=== Nächste Schritte ==="
echo "1. Lade buchung-deploy.tar.gz auf deinen IONOS Server hoch"
echo "2. Entpacke: tar -xzf buchung-deploy.tar.gz"
echo "3. Kopiere config-production.php.template zu config.php und passe an"
echo "4. Erstelle MySQL Datenbank und importiere schema.sql"
echo "5. Führe Migrationen aus: database/migrations/*.sql"
echo "6. Setze Verzeichnisrechte: chmod 755 logs/ credentials/"
echo "7. Rufe /oauth-start.php auf für Google OAuth"
echo "8. Teste die Installation"
echo ""
echo "Siehe .deployment-checklist.md für Details!"
