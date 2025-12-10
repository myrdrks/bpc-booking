<?php
/**
 * Admin: Raumverwaltung
 */
ob_start();
require_once __DIR__ . '/config.php';

// Admin-Check
// Session wird bereits in config.php gestartet
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    header('Location: admin.php');
    exit;
}

$db = Database::getInstance();

// Neuen Raum erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $name = trim($_POST['room_name']);
    $description = trim($_POST['room_description']);
    $calendarId = trim($_POST['google_calendar_id']);
    $priceMember = (float)$_POST['price_member'];
    $priceNonMember = (float)$_POST['price_non_member'];
    $capacity = (int)$_POST['capacity'];
    $minHours = (float)$_POST['min_hours'];
    $bufferBefore = (int)$_POST['buffer_before'];
    $bufferAfter = (int)$_POST['buffer_after'];
    $managerName = trim($_POST['manager_name'] ?? '');
    $managerEmail = trim($_POST['manager_email'] ?? '');
    $planningTemplate = trim($_POST['planning_template'] ?? '');
    $showPlanningTemplate = isset($_POST['show_planning_template']) ? 1 : 0;
    
    try {
        $stmt = $db->getConnection()->prepare(
            "INSERT INTO rooms (name, description, google_calendar_id, price_member, price_non_member, capacity, min_hours, buffer_before, buffer_after, manager_name, manager_email, planning_template, show_planning_template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $description, $calendarId, $priceMember, $priceNonMember, $capacity, $minHours, $bufferBefore, $bufferAfter, $managerName, $managerEmail, $planningTemplate, $showPlanningTemplate]);
        
        $_SESSION['success_message'] = "Raum '$name' erfolgreich erstellt!";
        ob_end_clean();
        header('Location: admin-rooms.php');
        exit;
    } catch (Exception $e) {
        $error = "Fehler beim Erstellen: " . $e->getMessage();
    }
}

// Raum aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $roomId = (int)$_POST['room_id'];
    $name = trim($_POST['room_name']);
    $description = trim($_POST['room_description']);
    $calendarId = trim($_POST['google_calendar_id']);
    $priceMember = (float)$_POST['price_member'];
    $priceNonMember = (float)$_POST['price_non_member'];
    $capacity = (int)$_POST['capacity'];
    $minHours = (float)$_POST['min_hours'];
    $bufferBefore = (int)$_POST['buffer_before'];
    $bufferAfter = (int)$_POST['buffer_after'];
    $managerName = trim($_POST['manager_name'] ?? '');
    $managerEmail = trim($_POST['manager_email'] ?? '');
    $planningTemplate = trim($_POST['planning_template'] ?? '');
    $showPlanningTemplate = isset($_POST['show_planning_template']) ? 1 : 0;
    
    try {
        $stmt = $db->getConnection()->prepare(
            "UPDATE rooms SET name = ?, description = ?, google_calendar_id = ?, price_member = ?, price_non_member = ?, capacity = ?, min_hours = ?, buffer_before = ?, buffer_after = ?, manager_name = ?, manager_email = ?, planning_template = ?, show_planning_template = ? WHERE id = ?"
        );
        $stmt->execute([$name, $description, $calendarId, $priceMember, $priceNonMember, $capacity, $minHours, $bufferBefore, $bufferAfter, $managerName, $managerEmail, $planningTemplate, $showPlanningTemplate, $roomId]);
        
        $_SESSION['success_message'] = "Raum erfolgreich aktualisiert!";
        ob_end_clean();
        header('Location: admin-rooms.php');
        exit;
    } catch (Exception $e) {
        $error = "Fehler beim Aktualisieren: " . $e->getMessage();
    }
}

// Raum löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $roomId = (int)$_POST['room_id'];
    
    try {
        // Prüfe ob Raum in Buchungen verwendet wird
        $stmt = $db->getConnection()->prepare(
            "SELECT COUNT(*) as count FROM bookings WHERE room_id = ?"
        );
        $stmt->execute([$roomId]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usage['count'] > 0) {
            $error = "Raum kann nicht gelöscht werden, da " . $usage['count'] . " Buchung(en) existieren.";
        } else {
            // Lösche zuerst Raum-Optionen-Zuordnungen
            $stmt = $db->getConnection()->prepare("DELETE FROM room_options WHERE room_id = ?");
            $stmt->execute([$roomId]);
            
            // Lösche Raum
            $stmt = $db->getConnection()->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$roomId]);
            
            $_SESSION['success_message'] = "Raum erfolgreich gelöscht!";
            ob_end_clean();
            header('Location: admin-rooms.php');
            exit;
        }
    } catch (Exception $e) {
        $error = "Fehler beim Löschen: " . $e->getMessage();
    }
}

// Option erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_option'])) {
    $name = trim($_POST['option_name']);
    $description = trim($_POST['option_description']);
    $price = (float)$_POST['option_price'];
    $category = trim($_POST['option_category']);
    $allowQuantity = isset($_POST['allow_quantity']) ? 1 : 0;
    $minQuantity = $allowQuantity ? max(1, (int)$_POST['min_quantity']) : 1;
    $isMandatory = isset($_POST['is_mandatory']) ? 1 : 0;
    $applyToRooms = !empty($_POST['apply_to_rooms']) ? implode(',', $_POST['apply_to_rooms']) : null;
    
    try {
        $stmt = $db->getConnection()->prepare(
            "INSERT INTO booking_options (name, description, price, category, allow_quantity, min_quantity, is_mandatory, apply_to_rooms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $description, $price, $category, $allowQuantity, $minQuantity, $isMandatory, $applyToRooms]);
        $success = "Option erfolgreich erstellt!";
    } catch (Exception $e) {
        $error = "Fehler beim Erstellen: " . $e->getMessage();
    }
}

// Option aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_option'])) {
    $optionId = (int)$_POST['option_id'];
    $name = trim($_POST['option_name']);
    $description = trim($_POST['option_description']);
    $price = (float)$_POST['option_price'];
    $category = trim($_POST['option_category']);
    $allowQuantity = isset($_POST['allow_quantity']) ? 1 : 0;
    $minQuantity = $allowQuantity ? max(1, (int)$_POST['min_quantity']) : 1;
    $isMandatory = isset($_POST['is_mandatory']) ? 1 : 0;
    $applyToRooms = !empty($_POST['apply_to_rooms']) ? implode(',', $_POST['apply_to_rooms']) : null;
    
    try {
        $stmt = $db->getConnection()->prepare(
            "UPDATE booking_options SET name = ?, description = ?, price = ?, category = ?, allow_quantity = ?, min_quantity = ?, is_mandatory = ?, apply_to_rooms = ? WHERE id = ?"
        );
        $stmt->execute([$name, $description, $price, $category, $allowQuantity, $minQuantity, $isMandatory, $applyToRooms, $optionId]);
        $success = "Option erfolgreich aktualisiert!";
    } catch (Exception $e) {
        $error = "Fehler beim Aktualisieren: " . $e->getMessage();
    }
}

// Option löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    $optionId = (int)$_POST['option_id'];
    
    try {
        // Prüfe ob Option in Buchungen verwendet wird
        $stmt = $db->getConnection()->prepare(
            "SELECT COUNT(*) as count FROM booking_options_selected WHERE option_id = ?"
        );
        $stmt->execute([$optionId]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usage['count'] > 0) {
            $error = "Option kann nicht gelöscht werden, da sie in " . $usage['count'] . " Buchung(en) verwendet wird.";
        } else {
            $stmt = $db->getConnection()->prepare("DELETE FROM booking_options WHERE id = ?");
            $stmt->execute([$optionId]);
            $success = "Option erfolgreich gelöscht!";
        }
    } catch (Exception $e) {
        $error = "Fehler beim Löschen: " . $e->getMessage();
    }
}

// Optionen zu Raum zuordnen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_options'])) {
    $roomId = (int)$_POST['room_id'];
    $assignType = $_POST['assign_type'] ?? 'all'; // 'all', 'category', 'single'
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Erst alle bestehenden Zuordnungen löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM room_options WHERE room_id = ?");
        $stmt->execute([$roomId]);
        
        $assignedCount = 0;
        
        if ($assignType === 'all') {
            // Alle Optionen zuordnen
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO room_options (room_id, option_id) 
                 SELECT ?, id FROM booking_options"
            );
            $stmt->execute([$roomId]);
            $assignedCount = $stmt->rowCount();
        } elseif ($assignType === 'category') {
            // Nach Kategorie zuordnen
            $categories = $_POST['categories'] ?? [];
            
            if (empty($categories)) {
                throw new Exception("Bitte wählen Sie mindestens eine Kategorie aus.");
            }
            
            $placeholders = str_repeat('?,', count($categories) - 1) . '?';
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO room_options (room_id, option_id) 
                 SELECT ?, id FROM booking_options WHERE category IN ($placeholders)"
            );
            $params = array_merge([$roomId], $categories);
            $stmt->execute($params);
            $assignedCount = $stmt->rowCount();
        } elseif ($assignType === 'single') {
            // Einzelne Optionen zuordnen
            $optionIds = $_POST['option_ids'] ?? [];
            
            if (empty($optionIds)) {
                throw new Exception("Bitte wählen Sie mindestens eine Option aus.");
            }
            
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO room_options (room_id, option_id) VALUES (?, ?)"
            );
            foreach ($optionIds as $optionId) {
                $stmt->execute([$roomId, (int)$optionId]);
                $assignedCount++;
            }
        }
        
        $db->getConnection()->commit();
        $_SESSION['success_message'] = "Optionen erfolgreich zugeordnet! ($assignedCount Optionen)";
        ob_end_clean();
        header('Location: admin-rooms.php');
        exit;
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $error = "Fehler beim Zuordnen: " . $e->getMessage();
    }
}

// Alle Räume laden
$rooms = $db->getConnection()->query(
    "SELECT * FROM rooms ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Alle Optionen laden
$options = $db->getConnection()->query(
    "SELECT * FROM booking_options ORDER BY category, name"
)->fetchAll(PDO::FETCH_ASSOC);

// Kategorien laden
$categories = $db->getConnection()->query(
    "SELECT DISTINCT category FROM booking_options ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

// Zugeordnete Optionen pro Raum laden
$roomOptionsMap = [];
foreach ($rooms as $room) {
    $stmt = $db->getConnection()->prepare(
        "SELECT option_id FROM room_options WHERE room_id = ?"
    );
    $stmt->execute([$room['id']]);
    $roomOptionsMap[$room['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Admin Layout verwenden
if (!empty($successMessage)) {
    $_SESSION['success_message'] = $successMessage;
}
if (!empty($errorMessage)) {
    $_SESSION['error_message'] = $errorMessage;
}

require_once __DIR__ . '/admin-header.php';
renderAdminHeader('Raumverwaltung', 'rooms');
?>
    <link rel="stylesheet" href="assets/css/booking.css">
    <style>
        .room-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .room-card h3 {
            margin-top: 0;
            color: #6F263D;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn-save {
            background: #E35205;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-save:hover {
            background: #229954;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            margin-top: 0;
            color: #1976d2;
        }
        .info-box ol {
            margin: 10px 0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #E35205;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .tab:hover {
            background: #e9ecef;
        }
        .tab.active {
            background: white;
            border-bottom-color: #E35205;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .options-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .options-table th,
        .options-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .options-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .options-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-service { background: #E35205; color: white; }
        .badge-food { background: #e67e22; color: white; }
        .badge-beverages { background: #9b59b6; color: white; }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }
        .btn-edit {
            background: #E35205;
            color: white;
        }
        .btn-delete {
            background: #6F263D;
            color: white;
        }
        .btn-add {
            background: #E35205;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .room-card.collapsed .room-form {
            display: none;
        }
        .room-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: -20px -20px 20px -20px;
            transition: background 0.2s;
        }
        .room-header:hover {
            background: #e9ecef;
        }
        .room-header h3 {
            margin: 0 !important;
            color: #6F263D;
        }
        .room-toggle {
            font-size: 1.5em;
            color: #6c757d;
            transition: transform 0.2s;
        }
        .room-card.collapsed .room-toggle {
            transform: rotate(-90deg);
        }
        .option-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .option-card.collapsed .option-form {
            display: none;
        }
        .option-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: -20px -20px 20px -20px;
            transition: background 0.2s;
        }
        .option-header:hover {
            background: #e9ecef;
        }
        .option-header h4 {
            margin: 0 !important;
            color: #333F48;
        }
        .option-toggle {
            font-size: 1.5em;
            color: #6c757d;
            transition: transform 0.2s;
        }
        .option-card.collapsed .option-toggle {
            transform: rotate(-90deg);
        }
    </style>
        
        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('rooms')">🏢 Räume</button>
            <button class="tab" onclick="switchTab('options')">⚙️ Buchungsoptionen</button>
        </div>
        
        <!-- Tab: Räume -->
        <div id="tab-rooms" class="tab-content active">
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn-add" onclick="showCreateRoomModal()">➕ Neuen Raum hinzufügen</button>
            <button class="btn-secondary" onclick="toggleAllRooms()">📋 Alle auf-/zuklappen</button>
        </div>
        
        <?php foreach ($rooms as $room): ?>
        <div class="room-card collapsed" id="room-card-<?php echo $room['id']; ?>">
            <div class="room-header" onclick="toggleRoom(<?php echo $room['id']; ?>)">
                <div>
                    <h3><?php echo h($room['name']); ?></h3>
                    <small style="color: #666;">
                        Kapazität: <?php echo $room['capacity']; ?> Personen | 
                        Min: <?php echo $room['min_hours']; ?>h | 
                        Mitglied: <?php echo number_format($room['price_member'], 2, ',', '.'); ?>€ | 
                        Nicht-Mitglied: <?php echo number_format($room['price_non_member'], 2, ',', '.'); ?>€
                    </small>
                </div>
                <span class="room-toggle">▼</span>
            </div>
            
            <div class="room-form">
                <form method="POST" action="" style="padding-top: 15px;">
                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                
                <div class="form-group">
                    <label>Raum-ID:</label>
                    <input type="text" value="<?php echo h($room['id']); ?>" disabled>
                </div>
                
                <!-- Embed Code & Link -->
                <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                    <h4 style="margin: 0 0 10px 0; color: #1976D2; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" 
                        onclick="document.getElementById('embed-section-<?php echo $room['id']; ?>').style.display = document.getElementById('embed-section-<?php echo $room['id']; ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.toggle-icon').textContent = document.getElementById('embed-section-<?php echo $room['id']; ?>').style.display === 'none' ? '▶' : '▼';">
                        <span>🔗 Buchungslink & Embed-Code</span>
                        <span class="toggle-icon">▶</span>
                    </h4>
                    
                    <div id="embed-section-<?php echo $room['id']; ?>" style="display: none; margin-top: 15px;">
                    <div style="margin-bottom: 15px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">Direkter Buchungslink:</label>
                        <input type="text" 
                               readonly 
                               value="https://booking.bremerpresseclub.de?room_id=<?php echo $room['id']; ?>" 
                               onclick="this.select(); document.execCommand('copy');"
                               style="width: 100%; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-family: monospace; font-size: 13px;"
                               title="Klicken zum Kopieren">
                        <small style="color: #666; font-size: 12px;">👆 Klicken zum Kopieren</small>
                    </div>
                    
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">Embed-Code für Website:</label>
                        <textarea readonly 
                                  onclick="this.select(); document.execCommand('copy');"
                                  rows="25"
                                  style="width: 100%; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-family: monospace; font-size: 12px; white-space: pre;"
                                  title="Klicken zum Kopieren"><style>
  /* optional sanfte Animation */
  #buchung-<?php echo $room['id']; ?> { width: 100%; border: 0; transition: height .2s ease; }
</style>

<iframe
  id="buchung-<?php echo $room['id']; ?>"
  src="https://booking.bremerpresseclub.de?room_id=<?php echo $room['id']; ?>"
  width="100%"
  height="0"
  loading="lazy"
  style="background: transparent;"
></iframe>

<script>
  const iframe = document.getElementById('buchung-<?php echo $room['id']; ?>');

  // Erlaubte Origin(s) der iframe-Seite – anpassen!
  const ALLOWED_ORIGINS = new Set([
    'https://booking.bremerpresseclub.de',
    'https://bremerpresseclub.de'
  ]);

  window.addEventListener('message', (event) => {
    // Origin prüfen
    if (!ALLOWED_ORIGINS.has(event.origin)) return;

    const data = event.data || {};
    if (data.type !== 'IFRAME_HEIGHT') return;

    // Falls mehrere iframes, via id zuordnen
    const target = (data.id && document.getElementById(data.id)) || iframe;

    if (target && typeof data.height === 'number' && data.height > 0) {
      target.style.height = data.height + 'px';
    }
  });

  // Erste Anpassung beim Laden
  iframe.addEventListener('load', () => {
    // Optional: Startsignal an Child senden
  });
</script></textarea>
                        <small style="color: #666; font-size: 12px;">👆 Klicken zum Kopieren - Fügen Sie diesen Code auf Ihrer Website ein</small>
                    </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="room_name_<?php echo $room['id']; ?>">Raumname *</label>
                    <input 
                        type="text" 
                        id="room_name_<?php echo $room['id']; ?>"
                        name="room_name" 
                        value="<?php echo h($room['name']); ?>"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="room_description_<?php echo $room['id']; ?>">Beschreibung</label>
                    <textarea 
                        id="room_description_<?php echo $room['id']; ?>"
                        name="room_description"><?php echo h($room['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="calendar_<?php echo $room['id']; ?>">
                        Google Calendar ID:
                    </label>
                    <input 
                        type="text" 
                        id="calendar_<?php echo $room['id']; ?>"
                        name="google_calendar_id" 
                        value="<?php echo h($room['google_calendar_id']); ?>"
                        placeholder="beispiel@group.calendar.google.com"
                        required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="manager_name_<?php echo $room['id']; ?>">👤 Raumverantwortliche/r:</label>
                        <input 
                            type="text" 
                            id="manager_name_<?php echo $room['id']; ?>"
                            name="manager_name" 
                            value="<?php echo h($room['manager_name'] ?? ''); ?>"
                            placeholder="Max Mustermann">
                        <small style="color: #666; font-size: 12px;">Name des Raummanagers</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="manager_email_<?php echo $room['id']; ?>">📧 Manager E-Mail:</label>
                        <input 
                            type="email" 
                            id="manager_email_<?php echo $room['id']; ?>"
                            name="manager_email" 
                            value="<?php echo h($room['manager_email'] ?? ''); ?>"
                            placeholder="manager@example.com">
                        <small style="color: #666; font-size: 12px;">Benachrichtigungen über Buchungen dieses Raums</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="capacity_<?php echo $room['id']; ?>">Kapazität (Personen):</label>
                        <input 
                            type="number" 
                            id="capacity_<?php echo $room['id']; ?>"
                            name="capacity" 
                            value="<?php echo h($room['capacity']); ?>"
                            min="1"
                            required>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_hours_<?php echo $room['id']; ?>">Mindeststunden:</label>
                        <input 
                            type="number" 
                            id="min_hours_<?php echo $room['id']; ?>"
                            name="min_hours" 
                            value="<?php echo h($room['min_hours'] ?? 1); ?>"
                            step="0.5"
                            min="0"
                            required>
                        <small style="color: #666; font-size: 12px;">Minimale Mietdauer in Stunden</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="price_member_<?php echo $room['id']; ?>">Preis Mitglieder (€):</label>
                        <input 
                            type="number" 
                            id="price_member_<?php echo $room['id']; ?>"
                            name="price_member" 
                            value="<?php echo h($room['price_member']); ?>"
                            step="0.01"
                            min="0"
                            required>
                        <small style="color: #666; font-size: 12px;">Preis pro Buchung für Vereinsmitglieder</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_non_member_<?php echo $room['id']; ?>">Preis Nicht-Mitglieder (€):</label>
                        <input 
                            type="number" 
                            id="price_non_member_<?php echo $room['id']; ?>"
                            name="price_non_member" 
                            value="<?php echo h($room['price_non_member']); ?>"
                            step="0.01"
                            min="0"
                            required>
                        <small style="color: #666; font-size: 12px;">Preis pro Buchung für Nicht-Mitglieder</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div class="form-group">
                        <label for="buffer_before_<?php echo $room['id']; ?>">⏱️ Puffer vorher (Minuten):</label>
                        <input 
                            type="number" 
                            id="buffer_before_<?php echo $room['id']; ?>"
                            name="buffer_before" 
                            value="<?php echo h($room['buffer_before'] ?? 15); ?>"
                            min="0"
                            max="120"
                            required>
                        <small style="color: #666; font-size: 12px;">Gesperrte Zeit vor jedem Termin (z.B. Aufbau, Reinigung)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="buffer_after_<?php echo $room['id']; ?>">⏱️ Puffer nachher (Minuten):</label>
                        <input 
                            type="number" 
                            id="buffer_after_<?php echo $room['id']; ?>"
                            name="buffer_after" 
                            value="<?php echo h($room['buffer_after'] ?? 15); ?>"
                            min="0"
                            max="120"
                            required>
                        <small style="color: #666; font-size: 12px;">Gesperrte Zeit nach jedem Termin (z.B. Abbau, Reinigung)</small>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>
                        <input type="checkbox" 
                               name="show_planning_template" 
                               id="show_planning_template_<?php echo $room['id']; ?>"
                               value="1" 
                               <?php echo ($room['show_planning_template'] ?? 1) ? 'checked' : ''; ?>>
                        ✅ Veranstaltungsplanungs-Template im Buchungsformular anzeigen
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="planning_template_<?php echo $room['id']; ?>">
                        📋 Veranstaltungsplanungs-Template:
                    </label>
                    <textarea 
                        id="planning_template_<?php echo $room['id']; ?>"
                        name="planning_template" 
                        rows="10"
                        style="font-family: monospace; font-size: 13px;"><?php echo h($room['planning_template'] ?? ''); ?></textarea>
                    <small style="color: #666; font-size: 12px;">Dieser Text erscheint als Platzhalter im Buchungsformular und hilft Nutzern, alle wichtigen Informationen anzugeben.</small>
                </div>
                
                <button type="submit" name="update_room" class="btn-save">
                    💾 Speichern
                </button>
            </form>
            
            <!-- Optionen zuordnen -->
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4>🔗 Buchungsoptionen zuordnen (Raum-ID: <?= $room['id'] ?>)</h4>
                <p style="color: #666; font-size: 14px;">Bestimmen Sie, welche Optionen für diesen Raum verfügbar sein sollen.</p>
                
                <?php 
                $assignedCount = count($roomOptionsMap[$room['id']] ?? []);
                $totalOptions = count($options);
                ?>
                <p><strong>Aktuell zugeordnet:</strong> <?= $assignedCount ?> von <?= $totalOptions ?> Optionen</p>
                
                <?php if ($assignedCount > 0): ?>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: #667eea; font-weight: 600;">Zugeordnete Optionen anzeigen</summary>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <?php 
                            foreach ($roomOptionsMap[$room['id']] as $optId) {
                                $opt = array_filter($options, fn($o) => $o['id'] == $optId);
                                $opt = reset($opt);
                                if ($opt) {
                                    echo '<li>' . h($opt['name']) . ' (' . h($opt['category']) . ')</li>';
                                }
                            }
                            ?>
                        </ul>
                    </details>
                <?php endif; ?>
                
                <form method="POST" action="" style="margin-top: 15px;" id="assignForm_<?= $room['id'] ?>">
                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                    
                    <div style="margin-bottom: 15px; padding: 10px; border: 2px solid #e9ecef; border-radius: 4px;">
                        <label>
                            <input type="radio" name="assign_type" value="all" checked onclick="updateAssignType_<?= $room['id'] ?>('all')">
                            <strong>Alle Optionen</strong> zuordnen (<?= count($options) ?> Optionen)
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 10px; border: 2px solid #e9ecef; border-radius: 4px;">
                        <label>
                            <input type="radio" name="assign_type" value="category" onclick="updateAssignType_<?= $room['id'] ?>('category')">
                            <strong>Nach Kategorie</strong> zuordnen:
                        </label>
                        <div id="categoryBox_<?= $room['id'] ?>" style="margin-left: 25px; margin-top: 10px; opacity: 0.5; pointer-events: none;">
                            <?php foreach ($categories as $cat): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="categories[]" value="<?= h($cat) ?>" class="category-checkbox">
                                    <?= h($cat) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 10px; border: 2px solid #e9ecef; border-radius: 4px;">
                        <label>
                            <input type="radio" name="assign_type" value="single" onclick="updateAssignType_<?= $room['id'] ?>('single')">
                            <strong>Einzelne Optionen</strong> auswählen:
                        </label>
                        <div id="singleBox_<?= $room['id'] ?>" style="margin-left: 25px; margin-top: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; opacity: 0.5; pointer-events: none;">
                            <?php foreach ($options as $opt): 
                                $isAssigned = in_array($opt['id'], $roomOptionsMap[$room['id']] ?? []);
                            ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="option_ids[]" value="<?= $opt['id'] ?>" <?= $isAssigned ? 'checked' : '' ?>>
                                    <?= h($opt['name']) ?> (<?= h($opt['category']) ?>) - <?= number_format($opt['price'], 2, ',', '.') ?> €
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="assign_options" class="btn-save">
                        🔗 Zuordnung speichern
                    </button>
                </form>
                
                <script>
                function updateAssignType_<?= $room['id'] ?>(type) {
                    const categoryBox = document.getElementById('categoryBox_<?= $room['id'] ?>');
                    const singleBox = document.getElementById('singleBox_<?= $room['id'] ?>');
                    
                    // Alle deaktivieren
                    categoryBox.style.opacity = '0.5';
                    categoryBox.style.pointerEvents = 'none';
                    singleBox.style.opacity = '0.5';
                    singleBox.style.pointerEvents = 'none';
                    
                    // Gewählten aktivieren
                    if (type === 'category') {
                        categoryBox.style.opacity = '1';
                        categoryBox.style.pointerEvents = 'auto';
                    } else if (type === 'single') {
                        singleBox.style.opacity = '1';
                        singleBox.style.pointerEvents = 'auto';
                    }
                }
                </script>
            </div>
            
            <form method="POST" style="margin-top: 20px;" onsubmit="return confirm('Raum wirklich löschen? Dies ist nur möglich, wenn keine Buchungen existieren.');">
                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                <button type="submit" name="delete_room" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">🗑️ Raum löschen</button>
            </form>
        </div>
        </div>
        <?php endforeach; ?>
        
        </div>
        
        <!-- Tab: Optionen -->
        <div id="tab-options" class="tab-content">
            <button class="btn-add" onclick="showCreateOptionModal()">➕ Neue Option hinzufügen</button>
            
            <table class="options-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th>Preis</th>
                        <th>Kategorie</th>
                        <th>Anzahl</th>
                        <th>Typ</th>
                        <th>Räume</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $option): ?>
                    <tr>
                        <td><?php echo h($option['id']); ?></td>
                        <td><?php echo h($option['name']); ?></td>
                        <td><?php echo h($option['description']); ?></td>
                        <td><?php echo number_format($option['price'], 2, ',', '.'); ?> €</td>
                        <td>
                            <span class="badge badge-<?php echo h($option['category']); ?>">
                                <?php echo h($option['category']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($option['allow_quantity']): ?>
                                <span style="color: #E35205;">✓ Ja (min. <?php echo $option['min_quantity']; ?>)</span>
                            <?php else: ?>
                                <span style="color: #95a5a6;">✗ Nein</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($option['is_mandatory']): ?>
                                <span style="color: #6F263D; font-weight: bold;">⚠️ Pflicht</span>
                            <?php else: ?>
                                <span style="color: #E35205;">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if (empty($option['apply_to_rooms'])) {
                                echo '<span style="color: #95a5a6;">Alle Räume</span>';
                            } else {
                                $roomIds = explode(',', $option['apply_to_rooms']);
                                $roomNames = [];
                                foreach ($roomIds as $rid) {
                                    $r = $db->fetchOne("SELECT name FROM rooms WHERE id = ?", [$rid]);
                                    if ($r) $roomNames[] = $r['name'];
                                }
                                echo h(implode(', ', $roomNames));
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn-small btn-edit" onclick='editOption(<?php echo json_encode($option); ?>)'>✏️ Bearbeiten</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich löschen?');">
                                <input type="hidden" name="option_id" value="<?php echo $option['id']; ?>">
                                <button type="submit" name="delete_option" class="btn-small btn-delete">🗑️ Löschen</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal: Raum erstellen -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <h2>Neuen Raum erstellen</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="newRoomName">Raumname *</label>
                    <input type="text" id="newRoomName" name="room_name" required>
                </div>
                
                <div class="form-group">
                    <label for="newRoomDescription">Beschreibung</label>
                    <textarea id="newRoomDescription" name="room_description" placeholder="z.B. Moderne Lounge mit Bar und Bestuhlung"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="newRoomCalendar">Google Calendar ID *</label>
                    <input type="text" id="newRoomCalendar" name="google_calendar_id" placeholder="beispiel@group.calendar.google.com" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="newRoomManagerName">👤 Raumverantwortliche/r</label>
                        <input type="text" id="newRoomManagerName" name="manager_name" placeholder="Max Mustermann">
                        <small style="color: #666; font-size: 12px;">Name des Raummanagers</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRoomManagerEmail">📧 Manager E-Mail</label>
                        <input type="email" id="newRoomManagerEmail" name="manager_email" placeholder="manager@example.com">
                        <small style="color: #666; font-size: 12px;">Benachrichtigungen über Buchungen</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="newRoomCapacity">Kapazität (Personen) *</label>
                        <input type="number" id="newRoomCapacity" name="capacity" min="1" value="20" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRoomMinHours">Mindeststunden *</label>
                        <input type="number" id="newRoomMinHours" name="min_hours" step="0.5" min="0" value="1" required>
                        <small style="color: #666; font-size: 12px;">Minimale Mietdauer</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="newRoomPriceMember">Preis Mitglieder (€) *</label>
                        <input type="number" id="newRoomPriceMember" name="price_member" step="0.01" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRoomPriceNonMember">Preis Nicht-Mitglieder (€) *</label>
                        <input type="number" id="newRoomPriceNonMember" name="price_non_member" step="0.01" min="0" value="0" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="newRoomBufferBefore">⏱️ Puffer vorher (Minuten) *</label>
                        <input type="number" id="newRoomBufferBefore" name="buffer_before" min="0" max="120" value="15" required>
                        <small style="color: #666;">Zeit vor Termin sperren</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRoomBufferAfter">⏱️ Puffer nachher (Minuten) *</label>
                        <input type="number" id="newRoomBufferAfter" name="buffer_after" min="0" max="120" value="15" required>
                        <small style="color: #666;">Zeit nach Termin sperren</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_planning_template" value="1" checked>
                        ✅ Veranstaltungsplanungs-Template im Buchungsformular anzeigen
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="newRoomPlanningTemplate">📋 Veranstaltungsplanungs-Template</label>
                    <textarea id="newRoomPlanningTemplate" name="planning_template" rows="8" style="font-family: monospace; font-size: 13px;" placeholder="Um Ihre Veranstaltung besser planen zu können...">Um Ihre Veranstaltung besser planen zu können, füllen Sie bitte folgende Punkte aus:

Veranstaltungstitel:

Programmablauf:
  Aufbau:
  Einlass:
  Beginn:
  Ende:
  Abbau:

Besondere Wünsche oder Anmerkungen:

Öffentliche Veranstaltung: Ja/Nein
Wenn Ja, soll die Veranstaltung auf unsere Website: Schicken Sie uns gerne einen Text und Bild per E-Mail.</textarea>
                    <small style="color: #666; font-size: 12px;">Platzhalter-Text für das Buchungsformular</small>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="create_room" class="btn-save">💾 Raum erstellen</button>
                    <button type="button" onclick="closeRoomModal()" style="background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Option erstellen/bearbeiten -->
    <div id="optionModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Neue Option</h2>
            <form id="optionForm" method="POST">
                <input type="hidden" id="optionId" name="option_id" value="">
                
                <div class="form-group">
                    <label for="optionName">Name *</label>
                    <input type="text" id="optionName" name="option_name" required>
                </div>
                
                <div class="form-group">
                    <label for="optionDescription">Beschreibung</label>
                    <textarea id="optionDescription" name="option_description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="optionPrice">Preis (€) *</label>
                    <input type="number" id="optionPrice" name="option_price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="optionCategory">Kategorie *</label>
                    <select id="optionCategory" name="option_category" required>
                        <option value="service">Service</option>
                        <option value="food">Speisen</option>
                        <option value="beverages">Getränke</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="allowQuantity" name="allow_quantity" value="1" checked onchange="toggleMinQuantity()" style="width: auto; margin-right: 10px;">
                        <span>Anzahl-Eingabe erlauben (z.B. für Speisen/Getränke pro Person)</span>
                    </label>
                    <small style="color: #666; margin-left: 30px;">Deaktivieren für Einmal-Optionen wie "Servicepauschale" oder "Technikeinweisung"</small>
                </div>

                <div class="form-group" id="minQuantityField">
                    <label for="minQuantity">Mindestanzahl:</label>
                    <input type="number" id="minQuantity" name="min_quantity" min="1" value="1" required>
                    <small style="color: #666;">Minimale Anzahl für diese Option (z.B. 20 für Catering-Pakete)</small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="isMandatory" name="is_mandatory" value="1" style="width: auto; margin-right: 10px;">
                        <span>Pflicht-Option (Pauschale)</span>
                    </label>
                    <small style="color: #666; margin-left: 30px;">Wird automatisch hinzugefügt und kann nicht abgewählt werden</small>
                </div>
                
                <div class="form-group">
                    <label for="applyToRooms">Gilt für Räume:</label>
                    <select id="applyToRooms" name="apply_to_rooms[]" multiple size="4" style="height: auto;">
                        <?php
                        $rooms = $db->fetchAll("SELECT id, name FROM rooms ORDER BY name");
                        foreach ($rooms as $room) {
                            echo '<option value="' . $room['id'] . '">' . h($room['name']) . '</option>';
                        }
                        ?>
                    </select>
                    <small style="color: #666;">Leer lassen = alle Räume. Strg/Cmd gedrückt halten für Mehrfachauswahl</small>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="create_option" id="submitBtn" class="btn-save">💾 Speichern</button>
                    <button type="button" onclick="closeModal()" style="background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        function showCreateRoomModal() {
            document.getElementById('roomModal').classList.add('active');
        }
        
        function closeRoomModal() {
            document.getElementById('roomModal').classList.remove('active');
        }
        
        // Modal schließen bei Klick außerhalb
        document.getElementById('roomModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoomModal();
            }
        });
        
        function showCreateOptionModal() {
            document.getElementById('modalTitle').textContent = 'Neue Option';
            document.getElementById('optionForm').reset();
            document.getElementById('optionId').value = '';
            document.getElementById('submitBtn').name = 'create_option';
            document.getElementById('submitBtn').textContent = '💾 Erstellen';
            document.getElementById('optionModal').classList.add('active');
        }
        
        function editOption(option) {
            document.getElementById('modalTitle').textContent = 'Option bearbeiten';
            document.getElementById('optionId').value = option.id;
            document.getElementById('optionName').value = option.name;
            document.getElementById('optionDescription').value = option.description || '';
            document.getElementById('optionPrice').value = option.price;
            document.getElementById('optionCategory').value = option.category;
            document.getElementById('allowQuantity').checked = option.allow_quantity == 1;
            document.getElementById('minQuantity').value = option.min_quantity || 1;
            document.getElementById('isMandatory').checked = option.is_mandatory == 1;
            
            // Räume-Auswahl setzen
            const applyToRoomsSelect = document.getElementById('applyToRooms');
            const selectedRooms = option.apply_to_rooms ? option.apply_to_rooms.split(',') : [];
            for (let i = 0; i < applyToRoomsSelect.options.length; i++) {
                applyToRoomsSelect.options[i].selected = selectedRooms.includes(applyToRoomsSelect.options[i].value);
            }
            
            toggleMinQuantity();
            document.getElementById('submitBtn').name = 'update_option';
            document.getElementById('submitBtn').textContent = '💾 Aktualisieren';
            document.getElementById('optionModal').classList.add('active');
        }
        
        function toggleMinQuantity() {
            const allowQuantity = document.getElementById('allowQuantity').checked;
            const minQuantityField = document.getElementById('minQuantityField');
            minQuantityField.style.display = allowQuantity ? 'block' : 'none';
        }
        
        function closeModal() {
            document.getElementById('optionModal').classList.remove('active');
        }
        
        // Modal schließen bei Klick außerhalb
        document.getElementById('optionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Toggle-Funktionen für einklappbare Karten
        function toggleRoom(roomId) {
            const card = document.getElementById('room-card-' + roomId);
            card.classList.toggle('collapsed');
        }
        
        function toggleOption(optionId) {
            const card = document.getElementById('option-card-' + optionId);
            card.classList.toggle('collapsed');
        }
        
        function toggleAllRooms() {
            const cards = document.querySelectorAll('.room-card');
            if (cards.length === 0) return;
            
            const firstCard = cards[0];
            const shouldCollapse = !firstCard.classList.contains('collapsed');
            
            cards.forEach(card => {
                if (shouldCollapse) {
                    card.classList.add('collapsed');
                } else {
                    card.classList.remove('collapsed');
                }
            });
        }
    </script>

<?php renderAdminFooter(); ?>
