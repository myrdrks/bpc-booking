<?php
// IONOS Test-Datei - Nach dem Test LÖSCHEN!

echo "<h1>IONOS PHP Test</h1>";

// PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Verzeichnis-Check
echo "<h2>Verzeichnisse:</h2>";
echo "<p>Current Dir: " . __DIR__ . "</p>";
echo "<p>config.php existiert: " . (file_exists(__DIR__ . '/config.php') ? 'JA' : 'NEIN') . "</p>";
echo "<p>classes/ existiert: " . (is_dir(__DIR__ . '/classes') ? 'JA' : 'NEIN') . "</p>";

// Dateirechte
if (file_exists(__DIR__ . '/config.php')) {
    echo "<p>config.php Rechte: " . substr(sprintf('%o', fileperms(__DIR__ . '/config.php')), -4) . "</p>";
}

// Apache Module
echo "<h2>Apache Module:</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "<ul>";
    foreach (['mod_rewrite', 'mod_headers', 'mod_deflate', 'mod_expires'] as $mod) {
        $exists = in_array($mod, $modules);
        echo "<li>$mod: " . ($exists ? '✓' : '✗') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>apache_get_modules() nicht verfügbar</p>";
}

// PHP-Einstellungen
echo "<h2>PHP-Einstellungen:</h2>";
echo "<p>display_errors: " . ini_get('display_errors') . "</p>";
echo "<p>error_reporting: " . error_reporting() . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";

// Versuche config.php zu laden
echo "<h2>Config-Test:</h2>";
if (file_exists(__DIR__ . '/config.php')) {
    try {
        require_once __DIR__ . '/config.php';
        echo "<p style='color:green'>✓ config.php erfolgreich geladen</p>";
        echo "<p>DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'nicht definiert') . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Fehler beim Laden: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ config.php nicht gefunden!</p>";
}

phpinfo();
?>
