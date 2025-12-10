<?php
/**
 * Raumbuchungssystem - Docker-spezifische Konfiguration
 * 
 * Diese Datei wird automatisch geladen, wenn Docker erkannt wird.
 */

// Datenbank-Konfiguration für Docker
// Da MySQL auf dem Host läuft, verwenden wir host.docker.internal
if (getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv')) {
    define('DB_HOST', 'host.docker.internal');
} else {
    define('DB_HOST', 'localhost');
}

define('DB_NAME', 'buchung');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_CHARSET', 'utf8mb4');

// URLs für Docker
if (getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv')) {
    define('APP_URL', 'http://localhost:8080');
    define('GOOGLE_REDIRECT_URI', 'http://localhost:8080/oauth-callback.php');
} else {
    define('APP_URL', 'http://localhost/buchung');
    define('GOOGLE_REDIRECT_URI', 'http://localhost/buchung/oauth-callback.php');
}
