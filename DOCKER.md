# Docker Setup - Raumbuchungssystem

## Voraussetzungen

- Docker & Docker Compose installiert
- MySQL läuft bereits auf dem Host (localhost)
- Port 8080 ist frei

## Schnellstart

### 1. Datenbank vorbereiten

Stelle sicher, dass MySQL läuft und die Datenbank existiert:

```bash
# Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS buchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Schema importieren
mysql -u root -p buchung < database/schema.sql
```

**Wichtig:** Passe in `config.php` die Datenbankzugangsdaten an (DB_USER, DB_PASS), falls diese von den Standardwerten abweichen.

### 2. Docker Container starten

```bash
# Container im Hintergrund starten
docker-compose up -d

# Oder im Vordergrund für Logs
docker-compose up
```

### 3. Composer-Abhängigkeiten installieren

Beim ersten Start werden automatisch die Composer-Abhängigkeiten installiert. Falls das nicht funktioniert:

```bash
docker-compose run --rm composer install
```

### 4. Anwendung öffnen

Öffne im Browser:
- **Raumübersicht:** http://localhost:8080/raeume.php
- **CLUB27:** http://localhost:8080/club27.php
- **Tagungsraum:** http://localhost:8080/tagungsraum.php
- **Club-Lounge:** http://localhost:8080/club-lounge.php
- **Admin-Panel:** http://localhost:8080/admin.php

## Nützliche Befehle

```bash
# Container anzeigen
docker-compose ps

# Logs anzeigen
docker-compose logs -f

# Container stoppen
docker-compose stop

# Container stoppen und entfernen
docker-compose down

# Container neu starten
docker-compose restart

# In den Container wechseln
docker exec -it bpc-buchung-web bash

# Composer-Pakete aktualisieren
docker-compose run --rm composer update
```

## Troubleshooting

### Problem: "Connection refused" zur Datenbank

Die Anwendung verwendet automatisch `host.docker.internal` als DB_HOST, wenn Docker erkannt wird. Falls das nicht funktioniert:

**Für Linux:**
```yaml
# In docker-compose.yml unter webserver hinzufügen:
extra_hosts:
  - "host.docker.internal:host-gateway"
```

**Für macOS/Windows:** Sollte automatisch funktionieren.

**Alternative:** MySQL auch in Docker laufen lassen:

```yaml
# In docker-compose.yml hinzufügen:
mysql:
  image: mysql:8.0
  container_name: bpc-buchung-mysql
  environment:
    MYSQL_ROOT_PASSWORD: password
    MYSQL_DATABASE: buchung
  ports:
    - "3306:3306"
  volumes:
    - mysql-data:/var/lib/mysql
  networks:
    - buchung-network

volumes:
  mysql-data:
```

Dann in `config.php` DB_HOST auf `mysql` ändern (Container-Name).

### Problem: Port 8080 bereits belegt

Ändere in `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Oder einen anderen freien Port
```

Dann öffne http://localhost:8081

### Problem: Dateirechte

Falls Fehler auftreten wegen fehlenden Schreibrechten:

```bash
# Logs- und Upload-Verzeichnisse erstellen
mkdir -p logs uploads credentials
chmod -R 755 logs uploads credentials

# Oder im Container:
docker exec -it bpc-buchung-web bash -c "chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads"
```

### Problem: Composer-Abhängigkeiten fehlen

```bash
# Manuell installieren
docker-compose run --rm composer install --ignore-platform-reqs

# Oder im laufenden Container
docker exec -it bpc-buchung-web bash
cd /var/www/html
composer install
```

### Problem: .htaccess wird nicht beachtet

Der Container aktiviert automatisch `mod_rewrite` und `AllowOverride All`. Falls es trotzdem nicht funktioniert:

```bash
# Container neu starten
docker-compose restart

# Oder Apache-Config prüfen
docker exec -it bpc-buchung-web cat /etc/apache2/apache2.conf | grep AllowOverride
```

## Entwicklung

### PHP-Erweiterungen hinzufügen

Bearbeite `docker-compose.yml` im `command`-Bereich:

```yaml
command: >
  bash -c "
  docker-php-ext-install pdo pdo_mysql mysqli gd zip && 
  ...
  "
```

### PHP-Version ändern

Ändere in `docker-compose.yml`:
```yaml
image: php:8.3-apache  # Oder php:7.4-apache
```

### Umgebungsvariablen

Erstelle eine `.env` Datei:
```env
DB_HOST=host.docker.internal
DB_NAME=buchung
DB_USER=root
DB_PASS=password
```

## Produktion

Für Produktionsumgebungen solltest du:
1. Eigenes Dockerfile erstellen
2. Multi-Stage Build verwenden
3. Composer-Abhängigkeiten im Build-Prozess installieren
4. Umgebungsvariablen für sensible Daten nutzen
5. HTTPS aktivieren
6. Nicht im Development-Modus laufen lassen

## Support

Bei Problemen prüfe die Logs:
```bash
# Apache-Logs
docker-compose logs webserver

# PHP-Fehler
docker exec -it bpc-buchung-web tail -f /var/www/html/logs/error-*.log
```
