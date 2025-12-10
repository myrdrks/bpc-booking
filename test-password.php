<?php
/**
 * Test: Passwort-Hash überprüfen
 */

// Standard Laravel/PHP Passwort für "admin123"
$standardHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "=== Passwort Hash Test ===\n\n";

// Test 1: Standard Hash
echo "Test 1: Standard Laravel Hash\n";
echo "Hash: $standardHash\n";
echo "Passwort 'admin123' passt? " . (password_verify('admin123', $standardHash) ? "JA ✓" : "NEIN ✗") . "\n\n";

// Test 2: Neuen Hash erstellen
$newHash = password_hash('admin123', PASSWORD_DEFAULT);
echo "Test 2: Neuer PHP Hash\n";
echo "Hash: $newHash\n";
echo "Passwort 'admin123' passt? " . (password_verify('admin123', $newHash) ? "JA ✓" : "NEIN ✗") . "\n\n";

// Test 3: Datenbank-Hash prüfen
require_once __DIR__ . '/config.php';

try {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", ['admin']);
    
    if ($user) {
        echo "Test 3: Datenbank Hash\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Hash aus DB: " . $user['password_hash'] . "\n";
        echo "Passwort 'admin123' passt? " . (password_verify('admin123', $user['password_hash']) ? "JA ✓" : "NEIN ✗") . "\n\n";
        
        // Update mit neuem Hash, falls fehlerhaft
        if (!password_verify('admin123', $user['password_hash'])) {
            echo "Hash ist fehlerhaft! Erstelle neuen Hash...\n";
            $correctHash = password_hash('admin123', PASSWORD_DEFAULT);
            $db->query("UPDATE admin_users SET password_hash = ? WHERE id = ?", [$correctHash, $user['id']]);
            echo "✓ Neuer Hash gespeichert!\n";
            echo "Versuche jetzt erneut: admin / admin123\n";
        }
    } else {
        echo "ERROR: Kein Admin-User gefunden!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Testergebnis ===\n";
echo "Login-Daten:\n";
echo "Benutzername: admin\n";
echo "Passwort: admin123\n";
?>
