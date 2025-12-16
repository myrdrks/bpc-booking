<?php
/**
 * Admin-Panel für Buchungsverwaltung
 */
ob_start(); // Output Buffering starten
require_once __DIR__ . '/config.php';

// Einfache Session-basierte Authentifizierung
// Session wird bereits in config.php gestartet

// Login-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM admin_users WHERE username = ? AND active = 1", [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_name'] = $user['full_name'];
        
        // Last login aktualisieren
        $db->query("UPDATE admin_users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        
        ob_end_clean(); // Buffer leeren vor Redirect
        
        // Wenn Passwortänderung erforderlich ist, dorthin umleiten
        if ($user['force_password_change']) {
            header('Location: admin-change-password.php');
        } else {
            header('Location: admin.php');
        }
        exit;
    } else {
        $loginError = 'Ungültige Anmeldedaten';
    }
}

// Logout-Handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Authentifizierung prüfen
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // Login-Formular anzeigen
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link rel="stylesheet" href="assets/css/booking.css">
    </head>
    <body>
        <div class="admin-login">
            <h1>Admin-Bereich</h1>
            
            <?php if (isset($loginError)): ?>
                <div class="alert alert-error"><?= h($loginError) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="username">Benutzername:</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Passwort:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary">Anmelden</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Prüfe ob Passwortänderung erforderlich ist
$db = Database::getInstance();
$currentAdmin = $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", [$_SESSION['admin_user']]);
if ($currentAdmin && $currentAdmin['force_password_change']) {
    ob_end_clean();
    header('Location: admin-change-password.php');
    exit;
}

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $bookingId = $_POST['booking_id'] ?? null;
    
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ungültiger CSRF-Token');
    }
    
    $bookingModel = new Booking();
    $roomModel = new Room();
    
    switch ($action) {
        case 'confirm':
            try {
                $booking = $bookingModel->getBookingById($bookingId);
                
                if ($booking) {
                    // Status aktualisieren
                    $bookingModel->updateStatus($bookingId, 'confirmed', $_SESSION['admin_user']);
                    
                    // Google Calendar Event aktualisieren (Präfix "Anfrage:" entfernen)
                    try {
                        $calendarService = new GoogleCalendarService();
                        $room = $roomModel->getRoomById($booking['room_id']);
                        
                        if ($booking['google_event_id'] && $room && !empty($room['google_calendar_id'])) {
                            // Event aktualisieren - Präfix entfernen
                            $eventTitle = $booking['customer_name']; // Ohne "Anfrage:"
                            $eventDescription = "BESTÄTIGT\n\n" .
                                "Kunde: " . $booking['customer_name'] . "\n" .
                                "Email: " . $booking['customer_email'] . "\n" .
                                "Telefon: " . $booking['customer_phone'] . "\n" .
                                "Personen: " . $booking['num_persons'] . "\n" .
                                "Mitglied: " . ($booking['is_member'] ? 'Ja' : 'Nein') . "\n" .
                                "Gesamtpreis: " . number_format($booking['total_price'], 2, ',', '.') . " €\n\n" .
                                ($booking['notes'] ? "Bemerkungen:\n" . $booking['notes'] : '');
                            
                            $calendarService->updateEvent(
                                $room['google_calendar_id'],
                                $booking['google_event_id'],
                                $eventTitle,
                                $eventDescription,
                                $booking['booking_date'] . ' ' . $booking['start_time'],
                                $booking['booking_date'] . ' ' . $booking['end_time']
                            );
                        }
                    } catch (Exception $e) {
                        logError('Google Calendar Update Fehler: ' . $e->getMessage(), ['booking_id' => $bookingId]);
                    }
                    
                    // E-Mail an Kunden senden
                    require_once __DIR__ . '/classes/EmailService.php';
                    $emailService = new EmailService();
                    $emailService->sendBookingConfirmed($bookingId);
                    
                    $_SESSION['success_message'] = 'Buchung wurde bestätigt und im Kalender aktualisiert.';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Fehler: ' . $e->getMessage();
                logError('Admin Bestätigung fehlgeschlagen: ' . $e->getMessage());
            }
            break;
            
        case 'reject':
            try {
                $booking = $bookingModel->getBookingById($bookingId);
                
                if ($booking) {
                    $bookingModel->updateStatus($bookingId, 'rejected', $_SESSION['admin_user']);
                    
                    // Google Calendar Event löschen
                    try {
                        if ($booking['google_event_id']) {
                            $calendarService = new GoogleCalendarService();
                            $room = $roomModel->getRoomById($booking['room_id']);
                            
                            if ($room && !empty($room['google_calendar_id'])) {
                                $calendarService->deleteEvent($room['google_calendar_id'], $booking['google_event_id']);
                            }
                        }
                    } catch (Exception $e) {
                        logError('Google Calendar Löschung Fehler: ' . $e->getMessage(), ['booking_id' => $bookingId]);
                    }
                    
                    // E-Mail an Kunden senden
                    require_once __DIR__ . '/classes/EmailService.php';
                    $emailService = new EmailService();
                    $emailService->sendBookingRejected($bookingId, $_POST['rejection_reason'] ?? '');
                    
                    $_SESSION['success_message'] = 'Buchung wurde abgelehnt und aus dem Kalender entfernt.';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Fehler: ' . $e->getMessage();
            }
            break;
            
        case 'cancel':
            try {
                $booking = $bookingModel->getBookingById($bookingId);
                
                if ($booking && $booking['google_event_id']) {
                    // Google Calendar Event löschen
                    $calendarService = new GoogleCalendarService();
                    $calendarService->deleteEvent($booking['google_calendar_id'], $booking['google_event_id']);
                }
                
                $bookingModel->updateStatus($bookingId, 'cancelled', $_SESSION['admin_user']);
                $_SESSION['success_message'] = 'Buchung wurde storniert.';
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Fehler: ' . $e->getMessage();
            }
            break;
    }
    
    header('Location: admin.php');
    exit;
}

// Daten laden
$bookingModel = new Booking();
$roomModel = new Room();
$allRooms = $roomModel->getAllRooms();

// Filter und Sortierung aus URL-Parametern
$filters = [
    'room_id' => $_GET['room_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$sortBy = $_GET['sort_by'] ?? 'created_at';
$sortOrder = $_GET['sort_order'] ?? 'DESC';

$pendingBookings = $bookingModel->getBookingsByStatus('pending', 1, 100, $filters, $sortBy, $sortOrder);

// Pagination für bestätigte Buchungen
$confirmedPage = max(1, intval($_GET['confirmed_page'] ?? 1));
$perPage = 20;
$confirmedBookings = $bookingModel->getBookingsByStatus('confirmed', $confirmedPage, $perPage, $filters, $sortBy, $sortOrder);
$totalConfirmed = $bookingModel->getBookingsCountByStatus('confirmed', $filters);
$totalConfirmedPages = ceil($totalConfirmed / $perPage);

$recentBookings = $bookingModel->getBookingsByStatus('confirmed', 1, 10);

// Admin Layout verwenden
require_once __DIR__ . '/admin-header.php';
renderAdminHeader('Buchungsverwaltung', 'bookings');
?>
    <style>
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .booking-table th,
        .booking-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .booking-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .booking-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-confirmed { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        .badge-cancelled { background: #6c757d; color: white; }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
    </style>

<!-- Statistiken -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Offene Anfragen</h3>
                <div class="value"><?= count($pendingBookings) ?></div>
            </div>
            <div class="stat-card">
                <h3>Bestätigte Buchungen</h3>
                <div class="value"><?= $totalConfirmed ?></div>
            </div>
        </div>
        
        <!-- Filter und Sortierung -->
        <section class="booking-section" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
            <form method="GET" action="admin.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Suche</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           placeholder="Name, E-Mail oder Raum"
                           value="<?= h($_GET['search'] ?? '') ?>"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label for="room_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Raum</label>
                    <select id="room_id" 
                            name="room_id"
                            style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Alle Räume</option>
                        <?php foreach ($allRooms as $room): ?>
                            <option value="<?= $room['id'] ?>" <?= ($filters['room_id'] == $room['id']) ? 'selected' : '' ?>>
                                <?= h($room['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="date_from" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Von Datum</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from"
                           value="<?= h($_GET['date_from'] ?? '') ?>"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label for="date_to" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Bis Datum</label>
                    <input type="date" 
                           id="date_to" 
                           name="date_to"
                           value="<?= h($_GET['date_to'] ?? '') ?>"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label for="sort_by" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Sortieren nach</label>
                    <select id="sort_by" 
                            name="sort_by"
                            style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Erstellt am</option>
                        <option value="booking_date" <?= $sortBy === 'booking_date' ? 'selected' : '' ?>>Buchungsdatum</option>
                        <option value="customer_name" <?= $sortBy === 'customer_name' ? 'selected' : '' ?>>Kundenname</option>
                        <option value="room_name" <?= $sortBy === 'room_name' ? 'selected' : '' ?>>Raumname</option>
                    </select>
                </div>
                
                <div>
                    <label for="sort_order" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reihenfolge</label>
                    <select id="sort_order" 
                            name="sort_order"
                            style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Absteigend</option>
                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Aufsteigend</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Filtern
                    </button>
                    <a href="admin.php" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none;">
                        Zurücksetzen
                    </a>
                </div>
            </form>
        </section>
        
        <!-- Offene Buchungsanfragen -->
        <section class="booking-section">
            <h2>Offene Buchungsanfragen</h2>
            
            <?php if (empty($pendingBookings)): ?>
                <p>Keine offenen Anfragen.</p>
            <?php else: ?>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Raum</th>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Kunde</th>
                            <th>Preis</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBookings as $booking): ?>
                            <tr>
                                <td>#<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($booking['room_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($booking['booking_date'])) ?></td>
                                <td>
                                    <?= substr($booking['start_time'], 0, 5) ?> - 
                                    <?= substr($booking['end_time'], 0, 5) ?>
                                </td>
                                <td>
                                    <?= h($booking['customer_name']) ?><br>
                                    <small><?= h($booking['customer_email']) ?></small>
                                </td>
                                <td><?= number_format($booking['total_price'], 2, ',', '.') ?> €</td>
                                <td>
                                    <span class="badge badge-<?= $booking['status'] ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="viewDetails(<?= $booking['id'] ?>)" 
                                                class="btn btn-sm btn-secondary">
                                            Details
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Bestätigen
                                            </button>
                                        </form>
                                        <button onclick="showRejectForm(<?= $booking['id'] ?>)" 
                                                class="btn btn-sm btn-danger">
                                            Ablehnen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        
        <!-- Bestätigte Buchungen -->
        <section class="booking-section">
            <h2>Aktuelle Buchungen</h2>
            
            <?php if (empty($confirmedBookings)): ?>
                <p>Keine bestätigten Buchungen.</p>
            <?php else: ?>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Raum</th>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Kunde</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($confirmedBookings as $booking): ?>
                            <tr>
                                <td>#<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($booking['room_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($booking['booking_date'])) ?></td>
                                <td>
                                    <?= substr($booking['start_time'], 0, 5) ?> - 
                                    <?= substr($booking['end_time'], 0, 5) ?>
                                </td>
                                <td><?= h($booking['customer_name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $booking['status'] ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewDetails(<?= $booking['id'] ?>)" 
                                            class="btn btn-sm btn-secondary">
                                        Details
                                    </button>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Buchung wirklich stornieren?')">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Stornieren
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php 
                // Erstelle Query-String für Pagination (behält Filter bei)
                $queryParams = $_GET;
                unset($queryParams['confirmed_page']);
                $queryString = http_build_query($queryParams);
                $pageLink = $queryString ? '&' . $queryString : '';
                
                if ($totalConfirmedPages > 1): ?>
                    <div class="pagination" style="margin-top: 1.5rem; text-align: center;">
                        <?php if ($confirmedPage > 1): ?>
                            <a href="?confirmed_page=<?= $confirmedPage - 1 ?><?= $pageLink ?>" class="btn btn-sm btn-secondary">
                                « Zurück
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $confirmedPage - 2);
                        $endPage = min($totalConfirmedPages, $confirmedPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?confirmed_page=1<?= $pageLink ?>" class="btn btn-sm btn-secondary">1</a>
                            <?php if ($startPage > 2): ?>
                                <span style="margin: 0 0.5rem;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $confirmedPage): ?>
                                <span class="btn btn-sm btn-primary" style="margin: 0 0.25rem;">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?confirmed_page=<?= $i ?><?= $pageLink ?>" class="btn btn-sm btn-secondary" style="margin: 0 0.25rem;">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalConfirmedPages): ?>
                            <?php if ($endPage < $totalConfirmedPages - 1): ?>
                                <span style="margin: 0 0.5rem;">...</span>
                            <?php endif; ?>
                            <a href="?confirmed_page=<?= $totalConfirmedPages ?><?= $pageLink ?>" class="btn btn-sm btn-secondary">
                                <?= $totalConfirmedPages ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($confirmedPage < $totalConfirmedPages): ?>
                            <a href="?confirmed_page=<?= $confirmedPage + 1 ?><?= $pageLink ?>" class="btn btn-sm btn-secondary">
                                Weiter »
                            </a>
                        <?php endif; ?>
                        
                        <div style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                            Seite <?= $confirmedPage ?> von <?= $totalConfirmedPages ?> 
                            (<?= $totalConfirmed ?> Buchungen gesamt)
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    
    <!-- Modal für Details (vereinfacht) -->
    <div id="detailsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; max-width:800px; margin:50px auto; padding:2rem; border-radius:8px; max-height:80vh; overflow-y:auto;">
            <div id="detailsContent"></div>
            <button onclick="closeModal()" class="btn btn-secondary">Schließen</button>
        </div>
    </div>
    
    <!-- Modal für Ablehnung -->
    <div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; max-width:500px; margin:100px auto; padding:2rem; border-radius:8px;">
            <h2>Buchung ablehnen</h2>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="booking_id" id="reject_booking_id" value="">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label for="rejection_reason">Grund für Ablehnung:</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-control"></textarea>
                </div>
                
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit" class="btn btn-danger">Ablehnen</button>
                    <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function viewDetails(bookingId) {
            fetch('api/booking-details.php?id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detailsContent').innerHTML = data.html;
                        document.getElementById('detailsModal').style.display = 'block';
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        function showRejectForm(bookingId) {
            document.getElementById('reject_booking_id').value = bookingId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
    </script>

<?php renderAdminFooter(); ?>
