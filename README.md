# Raumbuchungssystem

Ein vollständiges PHP-basiertes Raumbuchungssystem mit Google Calendar Integration, MySQL-Datenbank und Admin-Panel.

## Features

✅ **3 Räume** - CLUB27, Tagungsraum & Club-Lounge
✅ **Mitglieder-Preismodelle:**
   - CLUB27: Mitglieder 0€ Raummiete (+ 250€ Servicepauschale), Nicht-Mitglieder 500€
   - Tagungsraum: Mitglieder kostenfrei, Nicht-Mitglieder 100€/1. Std. + 50€/weitere Std.
   - Club-Lounge: Mitglieder kostenfrei, Nicht-Mitglieder 100€/1. Std. + 50€/weitere Std.
✅ **Catering-Optionen** (Brötchen, Sektempfang, Fingerfood, Heißgetränke, etc.)
✅ **Zusätzliche Services** (Technikeinweisung, Technikcheck)
✅ **Mehrfachbuchungen** pro Tag möglich (flexible Zeitauswahl)
✅ **Google Calendar Integration** für jeden Raum
✅ **Interaktiver Kalender** mit Echtzeit-Verfügbarkeit
✅ **MySQL Datenbankspeicherung**
✅ **E-Mail-Benachrichtigungen** für Kunden und Admin
✅ **Admin-Panel** zur Buchungsverwaltung
✅ **Responsive Design** für iframe-Einbindung
✅ **CSRF-Schutz** und sichere Datenbankabfragen

## Systemanforderungen

- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Apache/Nginx Webserver
- Composer (für Abhängigkeiten)
- Google Cloud Konto (für Calendar API)

## Schnellstart mit Docker 🐳

**Für lokale Entwicklung und Tests:**

```bash
# 1. Start-Skript ausführen (einmalig)
chmod +x start.sh
./start.sh

# 2. Im Browser öffnen
http://localhost:8080/raeume.php

# 3. Admin-Login
Benutzer: admin
Passwort: admin123
```

**Voraussetzungen:**
- Docker & Docker Compose installiert
- MySQL läuft auf localhost
- Port 8080 ist frei

**Siehe auch:** [DOCKER.md](DOCKER.md) für Details

---

## Installation (Produktion)

### 1. Dateien hochladen

Laden Sie alle Dateien in Ihr Webverzeichnis hoch:

```
buchung/
├── api/
│   └── availability.php
├── assets/
│   ├── css/
│   │   └── booking.css
│   └── js/
│       └── booking.js
├── classes/
│   ├── Booking.php
│   ├── BookingOption.php
│   ├── Database.php
│   ├── EmailService.php
│   ├── GoogleCalendarService.php
│   └── Room.php
├── database/
│   └── schema.sql
├── admin.php
├── booking-success.php
├── config.php
├── index.php
├── oauth-callback.php
└── process-booking.php
```

### 2. Datenbank einrichten

1. Erstellen Sie eine neue MySQL-Datenbank:
```sql
CREATE DATABASE raumbuchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importieren Sie das Schema:
```bash
mysql -u username -p raumbuchung < database/schema.sql
```

### 3. Konfiguration anpassen

Bearbeiten Sie `config.php` und passen Sie folgende Einstellungen an:

#### Datenbank:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'raumbuchung');
define('DB_USER', 'ihr_db_user');
define('DB_PASS', 'ihr_db_passwort');
```

#### E-Mail (SMTP):
```php
define('SMTP_HOST', 'smtp.ihre-domain.de');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@ihre-domain.de');
define('SMTP_PASSWORD', 'ihr_smtp_passwort');
define('EMAIL_FROM', 'noreply@ihre-domain.de');
define('ADMIN_EMAIL', 'admin@ihre-domain.de');
```

#### Anwendungs-URL:
```php
define('APP_URL', 'https://ihre-domain.de/buchung');
```

### 4. Composer-Abhängigkeiten installieren

```bash
cd buchung
composer require google/apiclient
composer require phpmailer/phpmailer
```

### 5. Google Calendar API einrichten

1. Gehen Sie zur [Google Cloud Console](https://console.cloud.google.com/)
2. Erstellen Sie ein neues Projekt
3. Aktivieren Sie die Google Calendar API
4. Erstellen Sie OAuth 2.0-Anmeldedaten (Desktop-App)
5. Laden Sie die Credentials herunter und speichern Sie sie als:
   `credentials/google-calendar-credentials.json`
6. Tragen Sie die Credentials in `config.php` ein:

```php
define('GOOGLE_CLIENT_ID', 'ihre-client-id');
define('GOOGLE_CLIENT_SECRET', 'ihr-client-secret');
define('GOOGLE_REDIRECT_URI', 'https://ihre-domain.de/buchung/oauth-callback.php');
```

7. Führen Sie die OAuth-Autorisierung durch:
   - Öffnen Sie im Browser: `https://ihre-domain.de/buchung/oauth-callback.php`
   - Folgen Sie dem Autorisierungsprozess
   - Die Token werden automatisch gespeichert

### 6. Google Calendar IDs eintragen

1. Erstellen Sie für jeden Raum einen eigenen Google Calendar:
   - **CLUB27**
   - **Tagungsraum**
   - **Club-Lounge**
   
2. Holen Sie sich die Calendar-IDs aus Google Calendar (Einstellungen → Kalender → Kalender-ID)

3. Aktualisieren Sie die Calendar-IDs in der Datenbank:

```sql
UPDATE rooms SET google_calendar_id = 'club27@group.calendar.google.com' WHERE id = 1;
UPDATE rooms SET google_calendar_id = 'tagungsraum@group.calendar.google.com' WHERE id = 2;
UPDATE rooms SET google_calendar_id = 'club-lounge@group.calendar.google.com' WHERE id = 3;
```

### 7. Verzeichnisrechte setzen

```bash
chmod 755 buchung/
chmod 644 buchung/*.php
chmod 755 buchung/classes/
chmod 644 buchung/classes/*.php
mkdir -p buchung/logs buchung/uploads buchung/credentials
chmod 755 buchung/logs buchung/uploads buchung/credentials
```

### 8. Admin-Zugang erstellen

Der Standard-Admin ist bereits in der Datenbank:
- **Benutzername:** `admin`
- **Passwort:** `admin123`

⚠️ **WICHTIG:** Ändern Sie das Passwort sofort nach dem ersten Login!

Um das Passwort zu ändern:
```sql
UPDATE admin_users 
SET password_hash = '$2y$10$NEUER_HASH_HIER' 
WHERE username = 'admin';
```

Oder erstellen Sie einen Hash mit PHP:
```php
<?php
echo password_hash('IhrNeuesPasswort', PASSWORD_DEFAULT);
```

## Verwendung

### Für Endbenutzer (Buchung)

1. Öffnen Sie die Buchungsseite: `https://ihre-domain.de/buchung/index.php?room_id=1`
2. Wählen Sie ein Datum im Kalender
3. Wählen Sie einen verfügbaren Zeitslot
4. Geben Sie Ihre Daten ein
5. Wählen Sie optional Extras aus
6. Senden Sie die Buchungsanfrage ab
7. Sie erhalten eine Bestätigungs-E-Mail

### Für Administratoren

1. Öffnen Sie das Admin-Panel: `https://ihre-domain.de/buchung/admin.php`
2. Loggen Sie sich ein
3. Sehen Sie alle offenen Buchungsanfragen
4. Bestätigen oder lehnen Sie Buchungen ab
5. Bestätigte Buchungen werden automatisch im Google Calendar eingetragen
6. Kunden erhalten automatisch eine Bestätigungs-E-Mail

### iframe-Einbindung

Für jeden Raum können Sie das Buchungsformular in eine bestehende Webseite einbinden:

```html
<iframe 
    src="https://ihre-domain.de/buchung/index.php?room_id=1" 
    width="100%" 
    height="1200" 
    frameborder="0"
    style="border: none;">
</iframe>
```

**Empfohlene iframe-Höhen:**
- Desktop: 1200px
- Tablet: 1400px
- Mobile: 1600px (oder responsive mit JavaScript)

### Raum-spezifische Seiten

Für jeden Raum gibt es bereits eigene Seiten:

- **CLUB27:** `club27.php` oder `index.php?room_id=1`
- **Tagungsraum:** `tagungsraum.php` oder `index.php?room_id=2`
- **Club-Lounge:** `club-lounge.php` oder `index.php?room_id=3`

**Raumübersicht:** `raeume.php` zeigt alle Räume auf einen Blick

## Anpassungen

### Räume bearbeiten

In der Datenbank können Sie Räume anpassen:

```sql
UPDATE rooms 
SET name = 'Ihr Raumname',
    description = 'Beschreibung',
    capacity = 25,
    price_member = 60.00,
    price_non_member = 90.00
WHERE id = 1;
```

### Buchungsoptionen hinzufügen

```sql
INSERT INTO booking_options (name, description, price, category) 
VALUES ('Ihre Option', 'Beschreibung', 12.50, 'food');
```

Kategorien: `beverages`, `food`, `equipment`, `service`

### Geschäftszeiten ändern

In `config.php`:

```php
define('BUSINESS_HOURS_START', '08:00');
define('BUSINESS_HOURS_END', '20:00');
define('BOOKING_TIME_SLOTS', 30); // Minuten
```

### Design anpassen

Bearbeiten Sie `assets/css/booking.css` für eigene Farben und Styling.

**Primärfarbe ändern:**
Suchen und ersetzen Sie `#667eea` durch Ihre gewünschte Farbe.

## Sicherheit

### Produktionsumgebung

1. Setzen Sie `DEBUG_MODE` auf `false` in `config.php`
2. Verwenden Sie HTTPS (SSL-Zertifikat)
3. Aktivieren Sie `cookie_secure` in der Session-Konfiguration
4. Ändern Sie Admin-Passwörter
5. Beschränken Sie Datenbankzugriff
6. Erstellen Sie regelmäßige Backups

### .htaccess Schutz

Erstellen Sie eine `.htaccess` in sensiblen Verzeichnissen:

```apache
# credentials/.htaccess
Order Deny,Allow
Deny from all

# logs/.htaccess
Order Deny,Allow
Deny from all
```

## Fehlerbehebung

### "Datenbankverbindung fehlgeschlagen"
- Prüfen Sie DB-Credentials in `config.php`
- Stellen Sie sicher, dass MySQL läuft
- Prüfen Sie, ob der DB-User die richtigen Rechte hat

### "Google Calendar Autorisierung erforderlich"
- Führen Sie `oauth-callback.php` aus
- Prüfen Sie, ob credentials.json vorhanden ist
- Stellen Sie sicher, dass die Redirect-URI korrekt ist

### E-Mails werden nicht versendet
- Prüfen Sie SMTP-Einstellungen in `config.php`
- Testen Sie mit einem SMTP-Tool
- Prüfen Sie Logs in `logs/error-DATUM.log`

### Keine verfügbaren Zeitslots
- Prüfen Sie Google Calendar Integration
- Prüfen Sie Geschäftszeiten in `config.php`
- Prüfen Sie, ob Calendar-IDs korrekt sind

## Support & Wartung

### Logs prüfen

Fehler werden automatisch geloggt in: `logs/error-YYYY-MM-DD.log`

### Datenbank-Backup

```bash
mysqldump -u username -p raumbuchung > backup_$(date +%Y%m%d).sql
```

### Updates

Bei Updates immer:
1. Backup erstellen
2. Dateien aktualisieren
3. Datenbankänderungen prüfen
4. Testen in Staging-Umgebung

## Lizenz

Dieses Projekt wurde speziell für Ihren Kunden entwickelt.

## Kontakt

Bei Fragen oder Problemen wenden Sie sich an den Entwickler.
