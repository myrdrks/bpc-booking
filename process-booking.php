<?php
/**
 * Buchungsverarbeitung
 */
ob_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// CSRF-Token validieren
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('Ungültiger CSRF-Token');
}

try {
    // Formulardaten validieren und sanitizen
    $roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $bookingDate = htmlspecialchars(strip_tags($_POST['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8');
    $startTime = htmlspecialchars(strip_tags($_POST['start_time'] ?? ''), ENT_QUOTES, 'UTF-8');
    $endTime = htmlspecialchars(strip_tags($_POST['end_time'] ?? ''), ENT_QUOTES, 'UTF-8');
    $customerName = htmlspecialchars(strip_tags($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $customerEmail = filter_input(INPUT_POST, 'customer_email', FILTER_VALIDATE_EMAIL);
    $customerPhone = htmlspecialchars(strip_tags($_POST['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $numPersons = filter_input(INPUT_POST, 'num_persons', FILTER_VALIDATE_INT);
    $isMember = isset($_POST['is_member']) ? 1 : 0;
    $notes = htmlspecialchars(strip_tags($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // Validierung
    $errors = [];
    
    if (!$roomId) {
        $errors[] = 'Ungültige Raum-ID';
    }
    
    if (!$bookingDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
        $errors[] = 'Ungültiges Datum';
    }
    
    if (!$startTime || !$endTime) {
        $errors[] = 'Bitte geben Sie Start- und Endzeit an';
    }
    
    if ($startTime >= $endTime) {
        $errors[] = 'Die Endzeit muss nach der Startzeit liegen';
    }
    
    if (!$customerName || strlen($customerName) < 2) {
        $errors[] = 'Bitte geben Sie Ihren Namen an';
    }
    
    if (!$customerEmail) {
        $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an';
    }
    
    // Raum existiert?
    $roomModel = new Room();
    $room = $roomModel->getRoomById($roomId);
    
    if (!$room) {
        $errors[] = 'Raum nicht gefunden';
    }
    
    // Datum in der Zukunft?
    $bookingDateObj = new DateTime($bookingDate);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($bookingDateObj < $today) {
        $errors[] = 'Das Buchungsdatum darf nicht in der Vergangenheit liegen';
    }
    
    // Nicht zu weit in der Zukunft?
    $maxDate = clone $today;
    $maxDate->modify('+' . MAX_BOOKING_DAYS_ADVANCE . ' days');
    
    if ($bookingDateObj > $maxDate) {
        $errors[] = 'Sie können maximal ' . MAX_BOOKING_DAYS_ADVANCE . ' Tage im Voraus buchen';
    }
    
    // Mindestbuchungsdauer prüfen
    $start = new DateTime($bookingDate . ' ' . $startTime);
    $end = new DateTime($bookingDate . ' ' . $endTime);
    $duration = $end->getTimestamp() - $start->getTimestamp();
    $durationHours = $duration / 3600;
    
    // Raum-spezifische Mindeststunden prüfen
    $minHours = (float)($room['min_hours'] ?? 1.0);
    if ($durationHours < $minHours) {
        $errors[] = 'Die Mindestbuchungsdauer für diesen Raum beträgt ' . $minHours . ' Stunde(n)';
    }
    
    // Fallback: Globale Mindestdauer (nur wenn keine raum-spezifische definiert)
    if ($minHours == 0 && $duration < MIN_BOOKING_DURATION * 60) {
        $errors[] = 'Die Mindestbuchungsdauer beträgt ' . MIN_BOOKING_DURATION . ' Minuten';
    }
    
    // Zeitüberschneidungen prüfen
    $bookingModel = new Booking();
    $hasOverlap = $bookingModel->checkTimeOverlap($roomId, $bookingDate, $startTime, $endTime);
    
    if ($hasOverlap) {
        $errors[] = 'Dieser Zeitraum ist bereits gebucht. Bitte wählen Sie einen anderen Zeitraum.';
    }
    
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = $_POST;
        ob_end_clean();
        header('Location: index.php?room_id=' . $roomId . '&error=1');
        exit;
    }
    
    // Preisberechnung
    $roomPrice = 0;
    
    // Spezielle Preislogik für jeden Raum
    if ($room['name'] === 'CLUB27') {
        // CLUB27: Mitglieder zahlen nur Servicepauschale (250€), Nicht-Mitglieder zahlen Raummiete (500€)
        $roomPrice = $isMember ? 0 : $room['price_non_member'];
    } else if ($room['name'] === 'Tagungsraum' || $room['name'] === 'Club-Lounge') {
        // Tagungsraum & Club-Lounge: Mitglieder kostenfrei, Nicht-Mitglieder stundenweise
        if (!$isMember) {
            // Stunden berechnen
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $hours = ceil($end->getTimestamp() - $start->getTimestamp()) / 3600;
            
            // Erste Stunde 100€, jede weitere 50€
            $roomPrice = 100 + (max(0, $hours - 1) * 50);
        } else {
            $roomPrice = 0;
        }
    } else {
        // Fallback für andere Räume
        $roomPrice = $room[$isMember ? 'price_member' : 'price_non_member'];
    }
    
    // Optionen verarbeiten
    $selectedOptions = [];
    $optionsPrice = 0;
    
    if (!empty($_POST['options'])) {
        $optionModel = new BookingOption();
        
        foreach ($_POST['options'] as $optionId) {
            $option = $optionModel->getOptionById($optionId);
            
            if ($option) {
                $minQuantity = $option['min_quantity'] ?? 1;
                $quantity = isset($_POST['option_quantities'][$optionId]) 
                    ? max($minQuantity, intval($_POST['option_quantities'][$optionId])) 
                    : 1;
                
                // Validierung: Prüfe ob Mindestanzahl eingehalten wird
                if ($option['allow_quantity'] && $quantity < $minQuantity) {
                    throw new Exception("Die Option '{$option['name']}' erfordert eine Mindestanzahl von {$minQuantity}.");
                }
                
                $price = $option['price'] * $quantity;
                $optionsPrice += $price;
                
                $selectedOptions[] = [
                    'option_id' => $optionId,
                    'quantity' => $quantity,
                    'price' => $option['price']
                ];
            }
        }
    }
    
    $totalPrice = $roomPrice + $optionsPrice;
    
    // Buchung erstellen
    $db = Database::getInstance();
    $db->beginTransaction();
    
    try {
        $bookingData = [
            'room_id' => $roomId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'is_member' => $isMember,
            'num_persons' => $numPersons,
            'room_price' => $roomPrice,
            'options_price' => $optionsPrice,
            'total_price' => $totalPrice,
            'notes' => $notes
        ];
        
        $result = $bookingModel->createBooking($bookingData);
        $bookingId = $result['booking_id'];
        $token = $result['token'];
        
        // Optionen speichern
        if (!empty($selectedOptions)) {
            $bookingModel->addBookingOptions($bookingId, $selectedOptions);
        }
        
        $db->commit();
        
        // Google Calendar Event erstellen (sofort als "Anfrage")
        try {
            $calendarService = new GoogleCalendarService();
            $booking = $bookingModel->getBookingById($bookingId);
            $room = $roomModel->getRoomById($roomId);
            
            if ($room && !empty($room['google_calendar_id'])) {
                $eventTitle = "Anfrage: " . $customerName;
                $eventDescription = "Buchungsanfrage\n\n" .
                    "Kunde: " . $customerName . "\n" .
                    "Email: " . $customerEmail . "\n" .
                    "Telefon: " . $customerPhone . "\n" .
                    "Personen: " . $numPersons . "\n" .
                    "Mitglied: " . ($isMember ? 'Ja' : 'Nein') . "\n" .
                    "Gesamtpreis: " . number_format($totalPrice, 2, ',', '.') . " €\n\n" .
                    ($notes ? "Bemerkungen:\n" . $notes : '');
                
                // Hauptevent erstellen
                $eventId = $calendarService->createEvent(
                    $room['google_calendar_id'],
                    $eventTitle,
                    $eventDescription,
                    $bookingDate . ' ' . $startTime,
                    $bookingDate . ' ' . $endTime
                );
                
                // Event-ID in Buchung speichern
                if ($eventId) {
                    $db->query("UPDATE bookings SET google_event_id = ? WHERE id = ?", [$eventId, $bookingId]);
                }
            }
        } catch (Exception $e) {
            // Fehler beim Kalendereintrag nicht kritisch - loggen aber fortfahren
            logError('Google Calendar Fehler bei Anfrage: ' . $e->getMessage(), ['booking_id' => $bookingId]);
        }
        
        // E-Mails versenden
        require_once __DIR__ . '/classes/EmailService.php';
        $emailService = new EmailService();
        
        // Bestätigung an Kunden
        $emailService->sendBookingConfirmation($bookingId);
        
        // Benachrichtigung an Admin
        $emailService->sendAdminNotification($bookingId);
        
        // Erfolgsseite
        $_SESSION['booking_success'] = true;
        $_SESSION['booking_id'] = $bookingId;
        ob_end_clean();
        header('Location: booking-success.php?token=' . $token);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Buchungsfehler: ' . $e->getMessage(), $_POST);
    $_SESSION['booking_errors'] = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'];
    ob_end_clean();
    header('Location: index.php?room_id=' . ($roomId ?? 1) . '&error=1');
    exit;
}
