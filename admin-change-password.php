<?php
/**
 * Admin: Passwort ändern (Pflicht beim ersten Login)
 */
ob_start();
require_once __DIR__ . '/config.php';

// Admin-Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    header('Location: admin.php');
    exit;
}

$db = Database::getInstance();
$username = $_SESSION['admin_user'];

// Admin-Daten laden
$admin = $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", [$username]);

if (!$admin) {
    session_destroy();
    ob_end_clean();
    header('Location: admin.php');
    exit;
}

$error = null;
$success = null;

// Passwort ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    // Validierung
    if (empty($currentPassword)) {
        $error = 'Bitte geben Sie Ihr aktuelles Passwort ein.';
    } elseif (!password_verify($currentPassword, $admin['password_hash'])) {
        $error = 'Das aktuelle Passwort ist nicht korrekt.';
    } elseif (empty($newPassword)) {
        $error = 'Bitte geben Sie ein neues Passwort ein.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else {
        try {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $db->getConnection()->prepare(
                "UPDATE admin_users SET password_hash = ?, email = ?, force_password_change = 0 WHERE id = ?"
            );
            $stmt->execute([$newPasswordHash, $email, $admin['id']]);
            
            $_SESSION['success_message'] = 'Passwort und E-Mail erfolgreich aktualisiert!';
            ob_end_clean();
            header('Location: admin.php');
            exit;
        } catch (Exception $e) {
            $error = 'Fehler beim Aktualisieren: ' . $e->getMessage();
        }
    }
}

// Admin Layout verwenden
if (!empty($success)) {
    $_SESSION['success_message'] = $success;
}
if (!empty($error)) {
    $_SESSION['error_message'] = $error;
}

require_once __DIR__ . '/admin-header.php';
renderAdminHeader('Passwort ändern', 'change-password');
?>
    <link rel="stylesheet" href="assets/css/booking.css">
    <style>
        .change-password-container {
            max-width: 500px;
            margin: 50px auto;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box h3 {
            margin-top: 0;
            color: #856404;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
    
    <div class="change-password-container">
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 10px;">🔐 Passwort ändern</h1>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Willkommen, <?= htmlspecialchars($admin['full_name'] ?? $username) ?>!</p>
        
        <?php if ($admin['force_password_change']): ?>
        <div class="warning-box">
            <h3>⚠️ Passwortänderung erforderlich</h3>
            <p>Sie verwenden noch die Standard-Zugangsdaten. Bitte ändern Sie Ihr Passwort und hinterlegen Sie Ihre E-Mail-Adresse, um fortzufahren.</p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Aktuelles Passwort *</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            
            <div class="form-group">
                <label for="email">E-Mail-Adresse *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                <div class="password-requirements">Ihre E-Mail für wichtige Benachrichtigungen</div>
            </div>
            
            <div class="form-group">
                <label for="new_password">Neues Passwort *</label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="8">
                <div class="password-requirements">Mindestens 8 Zeichen</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Neues Passwort bestätigen *</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
            </div>
            
            <button type="submit" name="change_password" class="btn-primary">
                💾 Passwort ändern
            </button>
        </form>
        
        <?php if (!$admin['force_password_change']): ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="admin.php" style="color: #667eea; text-decoration: none;">← Zurück zum Admin-Panel</a>
        </div>
        <?php endif; ?>
    </div>

<?php renderAdminFooter(); ?>
