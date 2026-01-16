#!/bin/bash
# Aktualisiere CLUB27 Preise und füge member_discount Feld hinzu
# Erstellt: 2026-01-16

echo "📊 Aktualisiere Datenbank für neue CLUB27 Preisstruktur..."

# Prüfe ob config.php existiert
if [ ! -f "config.php" ]; then
    echo "❌ Fehler: config.php nicht gefunden!"
    exit 1
fi

# Führe Migrationen aus
echo "1. Füge member_discount Feld hinzu..."
mysql --defaults-file=<(php -r "require 'config.php'; echo '[client]
user='.DB_USER.'
password='.DB_PASS.'
host='.DB_HOST.'
database='.DB_NAME;") < database/migrations/12_add_member_discount.sql

if [ $? -eq 0 ]; then
    echo "✅ member_discount Feld hinzugefügt"
else
    echo "⚠️  Warnung: member_discount Feld konnte nicht hinzugefügt werden (möglicherweise bereits vorhanden)"
fi

echo ""
echo "2. Aktualisiere CLUB27 Preise..."
mysql --defaults-file=<(php -r "require 'config.php'; echo '[client]
user='.DB_USER.'
password='.DB_PASS.'
host='.DB_HOST.'
database='.DB_NAME;") < database/update_club27_prices.sql

if [ $? -eq 0 ]; then
    echo "✅ CLUB27 Preise aktualisiert"
else
    echo "❌ Fehler beim Aktualisieren der CLUB27 Preise"
    exit 1
fi

echo ""
echo "✅ Datenbank erfolgreich aktualisiert!"
echo ""
echo "Neue Preisstruktur CLUB27:"
echo "- Mitglieder: 750€ - 250€ Rabatt + 250€ Service = 750€"
echo "- Externe: 750€ + 250€ Service = 1000€"
