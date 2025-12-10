<?php
/**
 * Admin Layout Header - Gemeinsames Layout für alle Admin-Seiten
 * 
 * Usage:
 * require_once __DIR__ . '/admin-header.php';
 * renderAdminHeader('Seitentitel', 'page-identifier');
 */

function renderAdminHeader($pageTitle = 'Admin-Bereich', $activePage = '') {
    // Prüfen ob Admin eingeloggt ist
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: admin-login.php');
        exit;
    }
    
    $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_user'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Andada+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/booking.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Andada Pro', Georgia, serif;
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar-header {
            padding: 1.5rem;
            background: #1a252f;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-sidebar-header h1 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .admin-sidebar-header .user-info {
            font-size: 0.9rem;
            color: #95a5a6;
        }
        
        .admin-nav {
            padding: 1rem 0;
        }
        
        .admin-nav-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .admin-nav-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }
        
        .admin-nav-item.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            font-weight: 600;
        }
        
        .admin-nav-item i {
            margin-right: 0.5rem;
            width: 20px;
            display: inline-block;
        }
        
        .admin-nav-section {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .admin-logout {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1rem 1.5rem;
            background: #1a252f;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-logout a {
            display: block;
            padding: 0.75rem;
            background: #c0392b;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-logout a:hover {
            background: #e74c3c;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
            background: #f5f7fa;
        }
        
        .admin-topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e1e8ed;
        }
        
        .admin-topbar h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .admin-content {
            padding: 2rem;
            max-width: 1400px;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            
            .admin-sidebar-header h1,
            .admin-sidebar-header .user-info,
            .admin-nav-item span,
            .admin-nav-section {
                display: none;
            }
            
            .admin-nav-item {
                text-align: center;
                padding: 1rem;
            }
            
            .admin-main {
                margin-left: 70px;
            }
            
            .admin-logout {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h1>📅 BPC Admin</h1>
            <div class="user-info"><?= h($adminName) ?></div>
        </div>
        
        <nav class="admin-nav">
            <a href="admin.php" class="admin-nav-item <?= $activePage === 'bookings' ? 'active' : '' ?>">
                <i>📋</i> <span>Buchungen</span>
            </a>
            
            <a href="admin-rooms.php" class="admin-nav-item <?= $activePage === 'rooms' ? 'active' : '' ?>">
                <i>🏢</i> <span>Raumverwaltung</span>
            </a>
            
            <div class="admin-nav-section">Kommunikation</div>
            
            <a href="admin-email-templates.php" class="admin-nav-item <?= $activePage === 'email-templates' ? 'active' : '' ?>">
                <i>📧</i> <span>E-Mail Templates</span>
            </a>
            
            <div class="admin-nav-section">Einstellungen</div>
            
            <a href="admin-settings.php" class="admin-nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
                <i>⚙️</i> <span>Systemeinstellungen</span>
            </a>
            
            <a href="admin-change-password.php" class="admin-nav-item <?= $activePage === 'password' ? 'active' : '' ?>">
                <i>🔐</i> <span>Passwort ändern</span>
            </a>
        </nav>
        
        <div class="admin-logout">
            <a href="admin.php?logout">Abmelden</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <div class="admin-topbar">
            <h2><?= h($pageTitle) ?></h2>
        </div>
        
        <div class="admin-content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    ✓ <?= h($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    ✗ <?= h($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
<?php
}

function renderAdminFooter() {
?>
        </div>
    </main>
</body>
</html>
<?php
}
?>
