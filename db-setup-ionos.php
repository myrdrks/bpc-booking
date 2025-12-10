<?php
// IONOS Database Setup Script
// NACH AUSFÜHRUNG SOFORT LÖSCHEN!!!

require_once 'config.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // SQL-Datei einlesen
    $sql = file_get_contents(__DIR__ . '/database/ionos-complete-setup.sql');
    
    if ($sql === false) {
        die("Fehler: ionos-complete-setup.sql nicht gefunden!");
    }
    
    echo "<h1>Database Setup</h1>";
    echo "<pre>";
    
    // SQL in einzelne Statements aufteilen
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { 
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt); 
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $success++;
            echo "✓ Statement ausgeführt\n";
        } catch (PDOException $e) {
            // Ignoriere "already exists" und "duplicate column" Fehler
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "✗ Fehler: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
                $errors++;
            } else {
                echo "⊘ Übersprungen (existiert bereits)\n";
            }
        }
    }
    
    echo "\n\n";
    echo "==========================================\n";
    echo "Setup abgeschlossen!\n";
    echo "Erfolg: $success | Fehler: $errors\n";
    echo "==========================================\n";
    echo "\n⚠️  LÖSCHE DIESE DATEI JETZT SOFORT! ⚠️\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>FEHLER</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
