<?php
/**
 * API-Endpunkt für Kalender-Verfügbarkeit
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Nur GET-Anfragen erlaubt'], 405);
}

$roomId = $_GET['room_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$roomId || !$date) {
    jsonResponse(['error' => 'room_id und date sind erforderlich'], 400);
}

try {
    // Raum-Daten abrufen
    $roomModel = new Room();
    $room = $roomModel->getRoomById($roomId);
    
    if (!$room) {
        jsonResponse(['error' => 'Raum nicht gefunden'], 404);
    }
    
    // Validiere Datum
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        jsonResponse(['error' => 'Ungültiges Datumsformat. Verwenden Sie Y-m-d'], 400);
    }
    
    // Prüfe ob Datum in der Vergangenheit liegt
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($dateObj < $today) {
        jsonResponse(['error' => 'Datum liegt in der Vergangenheit'], 400);
    }
    
    // Prüfe MAX_BOOKING_DAYS_ADVANCE
    $maxDate = clone $today;
    $maxDate->modify('+' . MAX_BOOKING_DAYS_ADVANCE . ' days');
    if ($dateObj > $maxDate) {
        jsonResponse(['error' => 'Datum liegt zu weit in der Zukunft'], 400);
    }
    
    // Google Calendar Verfügbarkeit abrufen
    $calendarService = new GoogleCalendarService();
    $availability = $calendarService->getAvailableSlots($room['google_calendar_id'], $date);
    
    // Lokale Buchungen aus Datenbank abrufen
    $bookingModel = new Booking();
    $localBookings = $bookingModel->getBookingsByRoomAndDate($roomId, $date);
    
    // Puffer-Zeiten aus Raum-Einstellungen
    $bufferBefore = (int)($room['buffer_before'] ?? 15);
    $bufferAfter = (int)($room['buffer_after'] ?? 15);
    
    // Google Calendar Slots mit Puffer verarbeiten
    $googleSlots = [];
    foreach ($availability['booked_slots'] ?? [] as $slot) {
        // Prüfe ob es ein Puffer-Event ist (beginnt mit 🔒)
        if (strpos($slot['title'], '🔒 Puffer') === 0) {
            // Puffer-Events werden übersprungen, da sie schon Teil der Hauptbuchung sind
            continue;
        }
        
        // Prüfe ob es ein ganztägiges Event ist
        if ($slot['start'] === 'all-day' || $slot['end'] === 'all-day' || 
            strpos($slot['start'], ':') === false || strpos($slot['end'], ':') === false) {
            // Ganztägiges Event - den ganzen Tag blockieren (08:00-23:59)
            $googleSlots[] = [
                'start' => '08:00',
                'end' => '23:59',
                'title' => $slot['title'] . ' (ganztägig)',
                'actual_start' => '08:00',
                'actual_end' => '23:59'
            ];
            continue;
        }
        
        // Puffer für normale Events hinzufügen
        try {
            $startTime = new DateTime($date . ' ' . $slot['start']);
            $endTime = new DateTime($date . ' ' . $slot['end']);
            
            $bufferStart = clone $startTime;
            $bufferStart->modify('-' . $bufferBefore . ' minutes');
            
            $bufferEnd = clone $endTime;
            $bufferEnd->modify('+' . $bufferAfter . ' minutes');
            
            $googleSlots[] = [
                'start' => $bufferStart->format('H:i'),
                'end' => $bufferEnd->format('H:i'),
                'title' => $slot['title'] . ' (inkl. Puffer)',
                'actual_start' => $slot['start'],
                'actual_end' => $slot['end']
            ];
        } catch (Exception $e) {
            // Fehler beim Parsen - Event überspringen
            logError('Google Calendar Event konnte nicht geparst werden: ' . json_encode($slot));
            continue;
        }
    }
    
    $localSlots = [];
    foreach ($localBookings as $booking) {
        // Ursprüngliche Buchungszeit
        $startTime = new DateTime($date . ' ' . $booking['start_time']);
        $endTime = new DateTime($date . ' ' . $booking['end_time']);
        
        // Puffer hinzufügen
        $bufferStart = clone $startTime;
        $bufferStart->modify('-' . $bufferBefore . ' minutes');
        
        $bufferEnd = clone $endTime;
        $bufferEnd->modify('+' . $bufferAfter . ' minutes');
        
        $localSlots[] = [
            'start' => $bufferStart->format('H:i'),
            'end' => $bufferEnd->format('H:i'),
            'title' => 'Gebucht (inkl. Puffer)',
            'actual_start' => substr($booking['start_time'], 0, 5),
            'actual_end' => substr($booking['end_time'], 0, 5)
        ];
    }
    
    jsonResponse([
        'success' => true,
        'room_id' => $roomId,
        'room_name' => $room['name'],
        'date' => $date,
        'buffer_before' => $bufferBefore,
        'buffer_after' => $bufferAfter,
        'min_hours' => (float)($room['min_hours'] ?? 1.0),
        'google_slots' => $googleSlots,
        'local_slots' => $localSlots,
        'available_slots' => $availability['available_slots'] ?? []
    ]);
    
} catch (Exception $e) {
    logError('API Fehler: ' . $e->getMessage());
    jsonResponse(['error' => 'Ein Fehler ist aufgetreten'], 500);
}
