# 🐳 Docker Setup - Schnellreferenz

## Befehle mit Makefile (empfohlen)

```bash
make help      # Zeigt alle verfügbaren Befehle
make install   # Erste Installation (DB + Container)
make up        # Container starten
make down      # Container stoppen
make restart   # Container neu starten
make logs      # Logs anzeigen
make shell     # Shell im Container öffnen
make composer  # Composer-Abhängigkeiten installieren
make status    # Container-Status anzeigen
```

## Befehle mit Docker Compose

```bash
# Container starten
docker-compose up -d

# Container stoppen
docker-compose down

# Logs anzeigen
docker-compose logs -f

# Status prüfen
docker-compose ps

# Shell im Container
docker exec -it bpc-buchung-web bash

# Container neu starten
docker-compose restart
```

## Befehle mit Bash-Skripten

```bash
# Alles einrichten und starten
./start.sh

# Container stoppen
./stop.sh
```

## URLs nach dem Start

- **Raumübersicht:** http://localhost:8080/raeume.php
- **CLUB27:** http://localhost:8080/club27.php
- **Tagungsraum:** http://localhost:8080/tagungsraum.php
- **Club-Lounge:** http://localhost:8080/club-lounge.php
- **Admin-Panel:** http://localhost:8080/admin.php

**Admin-Login:**
- Benutzer: `admin`
- Passwort: `admin123`

## Konfiguration

Die Datei `config.php` erkennt automatisch Docker und passt folgende Werte an:

- `DB_HOST` → `host.docker.internal` (statt `localhost`)
- `APP_URL` → `http://localhost:8080` (statt `http://localhost/buchung`)
- `GOOGLE_REDIRECT_URI` → `http://localhost:8080/oauth-callback.php`

## Troubleshooting

**Problem: "Connection refused" zur Datenbank**
```bash
# Prüfe ob MySQL läuft
mysql -u root -p -e "SELECT 1"

# Prüfe DB-Verbindung
docker exec -it bpc-buchung-web php -r "echo (new PDO('mysql:host=host.docker.internal;dbname=buchung', 'root', 'password'))->query('SELECT 1')->fetchColumn();"
```

**Problem: Port 8080 bereits belegt**
```yaml
# In docker-compose.yml ändern:
ports:
  - "8081:80"
```

**Problem: Composer-Abhängigkeiten fehlen**
```bash
docker-compose run --rm composer install
```

**Problem: Dateirechte**
```bash
docker exec -it bpc-buchung-web chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads
```

## Entwicklung

**PHP-Fehler anzeigen:**
```bash
docker exec -it bpc-buchung-web tail -f /var/www/html/logs/error-*.log
```

**Apache-Fehler anzeigen:**
```bash
docker-compose logs webserver
```

**Datenbank-Abfragen:**
```bash
mysql -u root -p buchung -e "SELECT * FROM rooms;"
```

**Container neu bauen:**
```bash
docker-compose down
docker-compose up -d --build
```

## Aufräumen

```bash
# Container stoppen und entfernen
docker-compose down

# Container + Volumes entfernen
docker-compose down -v

# Logs löschen
rm -rf logs/* uploads/*

# Alles bereinigen
make clean
```

## Produktion

Für Produktionsumgebungen:

1. Erstelle ein eigenes Dockerfile
2. Verwende Umgebungsvariablen für Credentials
3. Aktiviere HTTPS
4. Setze `DEBUG_MODE` auf `false`
5. Verwende einen Reverse Proxy (nginx)
6. Erstelle regelmäßige Backups

Siehe [README.md](README.md) für Produktions-Setup.
