<?php
/**
 * Admin Settings - Systemeinstellungen
 */
require_once __DIR__ . '/config.php';

// Prüfen ob Admin eingeloggt ist
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$db = Database::getInstance();
$successMessage = '';
$errorMessage = '';

// Settings aus Datenbank oder config.php holen
$stmt = $db->query("SELECT * FROM settings WHERE setting_key IN ('admin_email', 'admin_name')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fallback auf config.php Werte
$currentAdminEmail = $settings['admin_email'] ?? ADMIN_EMAIL;
$currentAdminName = $settings['admin_name'] ?? ADMIN_NAME;

// Settings speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Ungültiger CSRF-Token.';
    } else {
        $newAdminEmail = trim($_POST['admin_email'] ?? '');
        $newAdminName = trim($_POST['admin_name'] ?? '');
        
        // Validierung
        if (empty($newAdminEmail) || !filter_var($newAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        } elseif (empty($newAdminName)) {
            $errorMessage = 'Bitte geben Sie einen Namen ein.';
        } else {
            try {
                // Settings in Datenbank speichern (upsert)
                $db->query(
                    "INSERT INTO settings (setting_key, setting_value, updated_at) 
                     VALUES ('admin_email', ?, NOW()) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                    [$newAdminEmail, $newAdminEmail]
                );
                
                $db->query(
                    "INSERT INTO settings (setting_key, setting_value, updated_at) 
                     VALUES ('admin_name', ?, NOW()) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                    [$newAdminName, $newAdminName]
                );
                
                $currentAdminEmail = $newAdminEmail;
                $currentAdminName = $newAdminName;
                $successMessage = 'Einstellungen erfolgreich gespeichert!';
                
            } catch (Exception $e) {
                logError('Fehler beim Speichern der Settings: ' . $e->getMessage());
                $errorMessage = 'Fehler beim Speichern der Einstellungen.';
            }
        }
    }
}

// Admin Layout verwenden
if (!empty($successMessage)) {
    $_SESSION['success_message'] = $successMessage;
}
if (!empty($errorMessage)) {
    $_SESSION['error_message'] = $errorMessage;
}

require_once __DIR__ . '/admin-header.php';
renderAdminHeader('Systemeinstellungen', 'settings');
?>
    <style>
        
        .settings-section {
            margin-bottom: 30px;
        }
        
        .settings-section h2 {
            color: #333F48;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .settings-section p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333F48;
        }
        
        .form-group input[type="email"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            font-family: 'Andada Pro', Georgia, serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #E35205;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.9em;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info-box strong {
            color: #1976D2;
        }
        
        .btn-save {
            padding: 12px 30px;
            background: #E35205;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            font-family: 'Andada Pro', Georgia, serif;
            transition: background 0.3s;
        }
        
        .btn-save:hover {
            background: #c44604;
        }
        
        .current-config {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .current-config strong {
            color: #856404;
        }
        
        .current-config code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
        
        <div class="current-config">
            <strong>ℹ️ Hinweis:</strong> Diese Einstellungen werden in der Datenbank gespeichert und überschreiben die Werte aus der <code>config.php</code>.
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            
            <div class="settings-section">
                <h2>📧 Admin-Benachrichtigungen</h2>
                <p>
                    Legen Sie fest, an welche E-Mail-Adresse Benachrichtigungen über neue Buchungsanfragen gesendet werden sollen.
                </p>
                
                <div class="form-group">
                    <label for="admin_email">Admin E-Mail-Adresse *</label>
                    <input type="email" 
                           id="admin_email" 
                           name="admin_email" 
                           value="<?= h($currentAdminEmail) ?>"
                           required>
                    <small>An diese Adresse werden Benachrichtigungen über neue Buchungsanfragen gesendet.</small>
                </div>
                
                <div class="form-group">
                    <label for="admin_name">Admin Name *</label>
                    <input type="text" 
                           id="admin_name" 
                           name="admin_name" 
                           value="<?= h($currentAdminName) ?>"
                           required>
                    <small>Dieser Name erscheint als Empfänger in den E-Mail-Benachrichtigungen.</small>
                </div>
            </div>
            
            <div class="info-box">
                <strong>💡 Tipp:</strong> Nach dem Speichern werden alle neuen Buchungsanfragen an die hier angegebene E-Mail-Adresse gesendet. Die Kunden-E-Mails werden weiterhin von <code><?= h(EMAIL_FROM) ?></code> versendet.
            </div>
            
            <button type="submit" name="save_settings" class="btn-save">
                💾 Einstellungen speichern
            </button>
        </form>

<?php renderAdminFooter(); ?>
