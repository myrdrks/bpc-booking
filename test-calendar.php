<?php
/**
 * Debug-Script zum Testen der Google Calendar API
 */
require_once __DIR__ . '/config.php';

$calendarId = 'dn6pf5evn12i1uo0op2nmjmtmg@group.calendar.google.com';
$date = '2025-11-15';

echo "Testing Google Calendar API\n";
echo "Calendar ID: $calendarId\n";
echo "Date: $date\n\n";

try {
    $calendarService = new GoogleCalendarService();
    $result = $calendarService->getAvailableSlots($calendarId, $date);
    
    echo "Result:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
