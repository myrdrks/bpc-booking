<?php
/**
 * MySQL Datenbank Backup Script
 * 
 * Erstellt täglich ein Backup der MySQL-Datenbank
 * Kann per URL oder Cronjob aufgerufen werden
 * 
 * Aufruf: https://ihre-domain.de/buchung/backup-database.php?key=IHR_SICHERHEITS_KEY
 * 
 * WICHTIG: Setzen Sie einen sicheren KEY in der config.php!
 */

require_once __DIR__ . '/config.php';

// Sicherheitscheck: Backup-Key erforderlich
if (!defined('BACKUP_KEY') || empty(BACKUP_KEY)) {
    http_response_code(500);
    die('FEHLER: BACKUP_KEY ist nicht in config.php definiert!');
}

$providedKey = $_GET['key'] ?? '';
if ($providedKey !== BACKUP_KEY) {
    http_response_code(403);
    logError('Backup-Versuch mit ungültigem Key: ' . $_SERVER['REMOTE_ADDR']);
    die('Zugriff verweigert');
}

// Backup-Verzeichnis
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Prüfen ob heute schon ein Backup existiert
$today = date('Y-m-d');
$backupPattern = $backupDir . '/backup_' . $today . '_*.sql';
$existingBackups = glob($backupPattern);

if (!empty($existingBackups)) {
    http_response_code(200);
    echo "Backup für heute bereits vorhanden: " . basename($existingBackups[0]) . "\n";
    echo "Größe: " . round(filesize($existingBackups[0]) / 1024 / 1024, 2) . " MB\n";
    exit(0);
}

// Backup-Dateiname mit Zeitstempel
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

// Temporäre Datei für Fehlerausgabe
$errorFile = $backupDir . '/backup_error.log';

// mysqldump Befehl zusammenstellen
$command = sprintf(
    'mysqldump --user=%s --password=%s --host=%s --port=%d --single-transaction --routines --triggers --events %s > %s 2> %s',
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_HOST),
    DB_PORT ?? 3306,
    escapeshellarg(DB_NAME),
    escapeshellarg($backupFile),
    escapeshellarg($errorFile)
);

// Backup ausführen
exec($command, $output, $returnCode);

// Fehlerbehandlung
if ($returnCode !== 0) {
    http_response_code(500);
    $errorMsg = file_exists($errorFile) ? file_get_contents($errorFile) : 'Unbekannter Fehler';
    logError('Backup fehlgeschlagen: ' . $errorMsg);
    
    // Aufräumen
    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
    
    die('FEHLER: Backup fehlgeschlagen. Siehe Logs für Details.');
}

// Backup-Datei komprimieren (optional)
if (function_exists('gzencode') && file_exists($backupFile)) {
    $sqlContent = file_get_contents($backupFile);
    $gzFile = $backupFile . '.gz';
    
    if (file_put_contents($gzFile, gzencode($sqlContent, 9))) {
        // Original-SQL-Datei löschen, nur .gz behalten
        unlink($backupFile);
        $backupFile = $gzFile;
        $compressed = true;
    }
}

// Alte Backups löschen (älter als 30 Tage)
$oldBackups = glob($backupDir . '/backup_*.sql*');
$maxAge = 30 * 24 * 60 * 60; // 30 Tage in Sekunden
$deletedCount = 0;

foreach ($oldBackups as $oldBackup) {
    if (time() - filemtime($oldBackup) > $maxAge) {
        unlink($oldBackup);
        $deletedCount++;
    }
}

// Erfolg melden
$fileSize = filesize($backupFile);
$fileSizeMB = round($fileSize / 1024 / 1024, 2);

http_response_code(200);
echo "✓ Backup erfolgreich erstellt!\n\n";
echo "Datei: " . basename($backupFile) . "\n";
echo "Größe: " . $fileSizeMB . " MB";
if (isset($compressed)) {
    echo " (komprimiert)";
}
echo "\n";
echo "Pfad: " . $backupFile . "\n\n";

if ($deletedCount > 0) {
    echo "Alte Backups gelöscht: " . $deletedCount . "\n";
}

// Log-Eintrag
logError('Backup erfolgreich: ' . basename($backupFile) . ' (' . $fileSizeMB . ' MB)');

// Aufräumen
if (file_exists($errorFile)) {
    unlink($errorFile);
}

/**
 * Log-Funktion
 */
function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/backup-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
