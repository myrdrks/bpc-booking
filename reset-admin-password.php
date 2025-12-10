<?php
// TEMPORÄRES PASSWORD RESET SCRIPT
// NACH VERWENDUNG SOFORT LÖSCHEN!!!

require_once 'config.php';

echo "<h1>Admin Password Reset</h1>";
echo "<pre>";

try {
    $db = Database::getInstance();
    
    // Neues Passwort: admin123
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "Neuer Hash für 'admin123':\n";
    echo $newHash . "\n\n";
    
    // Update durchführen
    $stmt = $db->getConnection()->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$newHash]);
    
    echo "✓ Passwort für User 'admin' wurde zurückgesetzt!\n";
    echo "\nLogin-Daten:\n";
    echo "Benutzername: admin\n";
    echo "Passwort: admin123\n\n";
    
    // Verifizierung
    echo "Verifizierung:\n";
    $user = $db->fetchOne("SELECT username, password_hash FROM admin_users WHERE username = 'admin'");
    if ($user && password_verify('admin123', $user['password_hash'])) {
        echo "✓ Passwort funktioniert!\n";
    } else {
        echo "✗ Fehler bei der Verifizierung\n";
    }
    
    echo "\n\n⚠️⚠️⚠️ LÖSCHE DIESE DATEI JETZT SOFORT! ⚠️⚠️⚠️\n";
    
} catch (Exception $e) {
    echo "FEHLER: " . $e->getMessage();
}

echo "</pre>";
?>
