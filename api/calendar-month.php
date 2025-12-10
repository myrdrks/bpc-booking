<?php
/**
 * API-Endpunkt für Kalender-Monatsübersicht
 * Lädt alle Events für einen ganzen Monat
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Nur GET-Anfragen erlaubt'], 405);
}

$roomId = $_GET['room_id'] ?? null;
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;

if (!$roomId || !$year || !$month) {
    jsonResponse(['error' => 'room_id, year und month sind erforderlich'], 400);
}

try {
    // Raum-Daten abrufen
    $roomModel = new Room();
    $room = $roomModel->getRoomById($roomId);
    
    if (!$room) {
        jsonResponse(['error' => 'Raum nicht gefunden'], 404);
    }
    
    // Monatsgrenzen berechnen
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay = date('Y-m-t', strtotime($firstDay)); // Letzter Tag des Monats
    
    // Google Calendar Events für den ganzen Monat laden
    $calendarService = new GoogleCalendarService();
    $events = $calendarService->getEventsForMonth($room['google_calendar_id'], $firstDay, $lastDay);
    
    // Lokale Buchungen für den ganzen Monat laden
    $bookingModel = new Booking();
    $db = Database::getInstance();
    $localBookings = $db->fetchAll(
        "SELECT booking_date, COUNT(*) as count 
         FROM bookings 
         WHERE room_id = ? 
         AND booking_date BETWEEN ? AND ? 
         AND status IN ('pending', 'confirmed')
         GROUP BY booking_date",
        [$roomId, $firstDay, $lastDay]
    );
    
    // In assoziatives Array umwandeln (Datum => Anzahl)
    $localBookingDates = [];
    foreach ($localBookings as $booking) {
        $localBookingDates[$booking['booking_date']] = (int)$booking['count'];
    }
    
    // Google Events in Datumsformat umwandeln
    $googleEventDates = [];
    foreach ($events as $event) {
        $date = $event['date'];
        if (!isset($googleEventDates[$date])) {
            $googleEventDates[$date] = 0;
        }
        $googleEventDates[$date]++;
    }
    
    jsonResponse([
        'success' => true,
        'room_id' => $roomId,
        'year' => (int)$year,
        'month' => (int)$month,
        'google_events' => $googleEventDates,
        'local_bookings' => $localBookingDates
    ]);
    
} catch (Exception $e) {
    logError('Calendar Month API Fehler: ' . $e->getMessage());
    jsonResponse(['error' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()], 500);
}
