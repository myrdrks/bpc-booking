# Sicherheitshinweise für Deployment

## 🔒 Credentials-Schutz - Mehrschichtige Sicherheit

### Ebene 1: .htaccess Schutz (IMPLEMENTIERT)
- **Haupt-.htaccess**: Blockiert alle `.json` Dateien
- **credentials/.htaccess**: Blockiert gesamtes Verzeichnis
- **logs/.htaccess**: Blockiert Zugriff auf Logs
- **database/.htaccess**: Blockiert SQL-Dateien

### Ebene 2: Dateiberechtigungen (EMPFOHLEN)
```bash
# Nach Upload auf Server:
chmod 755 credentials/
chmod 600 credentials/*.json  # Nur Owner kann lesen
chmod 755 logs/
chmod 644 logs/*.log
```

### Ebene 3: Außerhalb des Web-Root (OPTIMAL)
Die sicherste Lösung ist, Credentials außerhalb des öffentlichen Verzeichnisses zu speichern.

#### IONOS Verzeichnisstruktur (typisch):
```
/kunden/
  └── homepages/XX/
      ├── htdocs/           ← Web-Root (öffentlich zugänglich)
      │   ├── index.php
      │   ├── admin.php
      │   └── ...
      └── private/          ← Außerhalb Web-Root (SICHER!)
          └── credentials/
              └── google-calendar-credentials.json
```

#### So implementieren:

**1. Credentials verschieben:**
```bash
mkdir -p ../private/credentials
mv credentials/google-calendar-credentials.json ../private/credentials/
```

**2. config.php anpassen:**
```php
// ALT:
define('GOOGLE_CREDENTIALS_PATH', __DIR__ . '/credentials/google-calendar-credentials.json');

// NEU (außerhalb Web-Root):
define('GOOGLE_CREDENTIALS_PATH', dirname(__DIR__) . '/private/credentials/google-calendar-credentials.json');
```

**3. Prüfen:**
```bash
# Diese URL darf NICHT funktionieren:
https://deine-domain.de/credentials/google-calendar-credentials.json
# → Sollte 403 Forbidden oder 404 Not Found zurückgeben
```

## 🛡️ Weitere Sicherheitsmaßnahmen

### 1. PHP-Konfiguration
```ini
# In php.ini oder .user.ini
expose_php = Off
display_errors = Off
log_errors = On
```

### 2. Sensible Dateien aus Git ausschließen
Die `.gitignore` ist bereits konfiguriert:
```
credentials/*.json
config.php
logs/*.log
```

### 3. Regelmäßige Sicherheits-Checks
```bash
# Teste ob Credentials erreichbar sind:
curl -I https://deine-domain.de/credentials/google-calendar-credentials.json
# Sollte 403 oder 404 zurückgeben, NICHT 200!

curl -I https://deine-domain.de/config.php
# Sollte 403 zurückgeben!

curl -I https://deine-domain.de/database/schema.sql
# Sollte 403 zurückgeben!
```

### 4. Umgebungsvariablen (Alternative)
Noch sicherer: Sensible Daten in Umgebungsvariablen speichern:

```php
// In config.php:
define('DB_PASS', getenv('DB_PASSWORD'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));

// Auf Server setzen (via .htaccess oder Server-Config):
SetEnv DB_PASSWORD "mein_passwort"
SetEnv SMTP_PASSWORD "mein_smtp_passwort"
```

## ⚠️ IONOS-spezifische Hinweise

### Web-Root finden:
Bei IONOS ist der Web-Root meist:
- `/kunden/homepages/XX/dXXXXXXX/htdocs/`

Alles was NICHT in `htdocs/` liegt, ist nicht öffentlich erreichbar!

### SSH-Zugang aktivieren:
1. IONOS Control Panel → Hosting → SSH-Zugang aktivieren
2. Via SSH einloggen: `ssh uXXXXXXX@DEINE-DOMAIN.de`
3. Credentials außerhalb von htdocs/ verschieben

### Backup-Strategie:
```bash
# Credentials sichern (verschlüsselt!)
tar -czf credentials-backup.tar.gz credentials/
openssl enc -aes-256-cbc -salt -in credentials-backup.tar.gz -out credentials-backup.tar.gz.enc
rm credentials-backup.tar.gz

# Entschlüsseln bei Bedarf:
openssl enc -aes-256-cbc -d -in credentials-backup.tar.gz.enc -out credentials-backup.tar.gz
```

## 🔍 Security Audit Checkliste

Nach Deployment prüfen:

- [ ] `https://domain.de/credentials/google-calendar-credentials.json` → 403/404
- [ ] `https://domain.de/config.php` → 403
- [ ] `https://domain.de/database/schema.sql` → 403
- [ ] `https://domain.de/logs/app.log` → 403
- [ ] `https://domain.de/.git/` → 403/404
- [ ] SSL-Zertifikat aktiv (HTTPS)
- [ ] Dateiberechtigungen korrekt gesetzt
- [ ] DEBUG_MODE = false
- [ ] error_reporting zeigt keine Fehler im Browser

## 📞 Im Notfall

Falls Credentials kompromittiert wurden:

1. **Sofort**: Google OAuth Credentials in Google Cloud Console widerrufen
2. **Neue Credentials erstellen** und erneut autorisieren
3. **Passwörter ändern**: Datenbank, SMTP, Admin-Accounts
4. **Logs prüfen**: Wer hat wann auf was zugegriffen?
5. **Security-Scan durchführen**: z.B. mit Sucuri oder Wordfence

## 🔗 Ressourcen

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [IONOS Security Guide](https://www.ionos.de/hilfe/sicherheit/)
