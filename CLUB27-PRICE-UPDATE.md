# CLUB27 Preisstruktur-Update

## Datum: 16. Januar 2026

## Änderungen

### Problem
Die CLUB27 Preise waren inkorrekt:
- Mitglieder: 0€ Raummiete + 250€ Service = 250€
- Externe: 500€ Raummiete + 250€ Service = 750€

### Lösung
Neue Preisstruktur:
- **Mitglieder**: 750€ Raummiete - 250€ Rabatt + 250€ Service = **750€ gesamt**
- **Externe**: 750€ Raummiete + 250€ Service = **1000€ gesamt**

## Durchgeführte Änderungen

### 1. Datenbank
- ✅ Migration erstellt: `12_add_member_discount.sql` - Fügt `member_discount` Feld hinzu
- ✅ Update-Script: `update_club27_prices.sql` - Aktualisiert CLUB27 Preise auf 750€
- ✅ Schema-Dateien aktualisiert (schema.sql, ionos-complete-setup.sql)

### 2. Backend (PHP)
- ✅ `process-booking.php`: Preisberechnung für CLUB27 auf 750€ mit 250€ Mitgliederrabatt
- ✅ `classes/Booking.php`: Unterstützung für `member_discount` Feld
- ✅ `api/booking-details.php`: Zeigt Mitgliederrabatt in Preisübersicht an

### 3. Frontend (HTML/JavaScript)
- ✅ `index.php`: 
  - JavaScript Preisberechnung aktualisiert
  - Rabatt-Zeile zur Preisübersicht hinzugefügt
  - Preisinformationen für CLUB27 aktualisiert
- ✅ `assets/js/booking.js`: PriceCalculator erweitert um `memberDiscount`
- ✅ `raeume.php`: Preisanzeige für CLUB27 aktualisiert

### 4. Dokumentation
- ✅ `README.md`: Preisstruktur aktualisiert

## Installation

1. **Datenbank aktualisieren:**
   ```bash
   ./update-club27-prices.sh
   ```

   Oder manuell:
   ```bash
   # Migration ausführen
   mysql -u [USER] -p [DATABASE] < database/migrations/12_add_member_discount.sql
   
   # Preise aktualisieren
   mysql -u [USER] -p [DATABASE] < database/update_club27_prices.sql
   ```

2. **Änderungen testen:**
   - Neue Buchung für CLUB27 als Mitglied erstellen
   - Neue Buchung für CLUB27 als Nicht-Mitglied erstellen
   - Preisberechnung prüfen

## Erwartete Ergebnisse

### Mitgliederbuchung (Buchung #000063)
```
Raummiete:          750,00 €
Mitglieder-Rabatt: -250,00 €
Service:            250,00 €
─────────────────────────────
Gesamt:             750,00 €
```

### Nicht-Mitgliederbuchung
```
Raummiete:          750,00 €
Service:            250,00 €
─────────────────────────────
Gesamt:           1.000,00 €
```

## Rollback (falls nötig)

Falls die Änderungen rückgängig gemacht werden müssen:

```sql
-- Preise zurücksetzen
UPDATE rooms 
SET price_member = 0.00, price_non_member = 500.00
WHERE name = 'CLUB27';

-- member_discount Spalte entfernen
ALTER TABLE bookings DROP COLUMN member_discount;
```
