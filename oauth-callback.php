<?php
/**
 * OAuth Callback für Google Calendar API
 */
require_once __DIR__ . '/config.php';

if (!isset($_GET['code'])) {
    // Erster Schritt: Weiterleitung zu Google
    $calendarService = new GoogleCalendarService(true); // skipTokenCheck = true
    $authUrl = $calendarService->getAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
}

try {
    // Zweiter Schritt: Code von Google erhalten
    $calendarService = new GoogleCalendarService(true); // skipTokenCheck = true
    $calendarService->authenticate($_GET['code']);
    echo '<h1>Erfolgreich authentifiziert!</h1>';
    echo '<p>Die Google Calendar Integration ist jetzt aktiv.</p>';
    echo '<p><a href="raeume.php">Zurück zum Buchungssystem</a></p>';
} catch (Exception $e) {
    echo '<h1>Fehler bei der Authentifizierung</h1>';
    echo '<p>' . h($e->getMessage()) . '</p>';
    echo '<p><a href="oauth-callback.php">Erneut versuchen</a></p>';
}
