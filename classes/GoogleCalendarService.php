<?php
/**
 * Google Calendar Integration für Raumbuchungssystem
 * 
 * Benötigt: composer require google/apiclient
 */

require_once __DIR__ . '/../vendor/autoload.php';

class GoogleCalendarService {
    private $client;
    private $service;
    
    public function __construct($skipTokenCheck = false) {
        $this->initializeClient($skipTokenCheck);
    }
    
    /**
     * Google API Client initialisieren
     */
    private function initializeClient($skipTokenCheck = false) {
        $this->client = new Google_Client();
        $this->client->setApplicationName(APP_NAME);
        $this->client->setScopes(Google_Service_Calendar::CALENDAR);
        
        // Prüfe ob Client ID und Secret direkt in config.php definiert sind
        if (defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET') && 
            !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET)) {
            $this->client->setClientId(GOOGLE_CLIENT_ID);
            $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
            $this->client->setRedirectUri(GOOGLE_REDIRECT_URI);
        } elseif (defined('GOOGLE_CREDENTIALS_PATH') && file_exists(GOOGLE_CREDENTIALS_PATH)) {
            // Fallback: Credentials-Datei
            $this->client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
        } else {
            throw new Exception('Google Calendar Credentials nicht konfiguriert. Bitte Client ID und Secret in config.php eintragen.');
        }
        
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        
        // Bei OAuth-Flow: Token-Check überspringen
        if ($skipTokenCheck) {
            return;
        }
        
        // Token laden oder erneuern
        $tokenPath = __DIR__ . '/../credentials/token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }
        
        // Token erneuern wenn abgelaufen
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else {
                // Neue Autorisierung erforderlich
                throw new Exception('Google Calendar Autorisierung erforderlich. Bitte oauth-callback.php aufrufen.');
            }
            
            // Token speichern
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        }
        
        $this->service = new Google_Service_Calendar($this->client);
    }
    
    /**
     * Verfügbare Zeitslots für einen Raum an einem Datum abrufen
     */
    public function getAvailableSlots($calendarId, $date) {
        try {
            // Für ganztägige Events müssen wir den ganzen Tag abfragen (00:00 - 23:59)
            $timeMin = new DateTime($date . ' 00:00:00');
            $timeMax = new DateTime($date . ' 23:59:59');
            
            $optParams = [
                'timeMin' => $timeMin->format('c'),
                'timeMax' => $timeMax->format('c'),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];
            
            $events = $this->service->events->listEvents($calendarId, $optParams);
            $bookedSlots = [];
            
            foreach ($events->getItems() as $event) {
                // Unterscheide zwischen ganztägigen Events (date) und zeitbasierten Events (dateTime)
                if ($event->start->dateTime) {
                    // Zeitbasiertes Event
                    $start = new DateTime($event->start->dateTime);
                    $end = new DateTime($event->end->dateTime);
                    
                    $bookedSlots[] = [
                        'start' => $start->format('H:i'),
                        'end' => $end->format('H:i'),
                        'title' => $event->getSummary()
                    ];
                } else if ($event->start->date) {
                    // Ganztägiges Event - keine Zeitangabe, nur Datum
                    $bookedSlots[] = [
                        'start' => 'all-day',
                        'end' => 'all-day',
                        'title' => $event->getSummary()
                    ];
                }
            }
            
            return [
                'success' => true,
                'booked_slots' => $bookedSlots,
                'available_slots' => $this->calculateAvailableSlots($bookedSlots, $date)
            ];
            
        } catch (Exception $e) {
            logError('Google Calendar API Fehler: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Kalender konnte nicht abgerufen werden.'
            ];
        }
    }
    
    /**
     * Events für einen ganzen Monat abrufen
     */
    public function getEventsForMonth($calendarId, $startDate, $endDate) {
        try {
            $timeMin = new DateTime($startDate . ' 00:00:00');
            $timeMax = new DateTime($endDate . ' 23:59:59');
            
            $optParams = [
                'timeMin' => $timeMin->format('c'),
                'timeMax' => $timeMax->format('c'),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];
            
            $events = $this->service->events->listEvents($calendarId, $optParams);
            $eventDates = [];
            
            foreach ($events->getItems() as $event) {
                // Datum des Events ermitteln
                if ($event->start->dateTime) {
                    // Zeitbasiertes Event
                    $start = new DateTime($event->start->dateTime);
                    $date = $start->format('Y-m-d');
                } else if ($event->start->date) {
                    // Ganztägiges Event
                    $date = $event->start->date;
                }
                
                if (isset($date)) {
                    $eventDates[] = [
                        'date' => $date,
                        'title' => $event->getSummary(),
                        'is_all_day' => !$event->start->dateTime
                    ];
                }
            }
            
            return $eventDates;
            
        } catch (Exception $e) {
            logError('Google Calendar Month API Fehler: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verfügbare Zeitslots berechnen
     */
    private function calculateAvailableSlots($bookedSlots, $date) {
        $available = [];
        $slotDuration = BOOKING_TIME_SLOTS; // Minuten
        
        $currentTime = new DateTime($date . ' ' . BUSINESS_HOURS_START);
        $endTime = new DateTime($date . ' ' . BUSINESS_HOURS_END);
        
        while ($currentTime < $endTime) {
            $slotStart = clone $currentTime;
            $currentTime->modify("+{$slotDuration} minutes");
            $slotEnd = clone $currentTime;
            
            // Prüfen ob Slot frei ist
            $isFree = true;
            foreach ($bookedSlots as $booked) {
                // Ganztägige Events blockieren den gesamten Tag
                if ($booked['start'] === 'all-day' || $booked['end'] === 'all-day') {
                    $isFree = false;
                    break;
                }
                
                $bookedStart = new DateTime($date . ' ' . $booked['start']);
                $bookedEnd = new DateTime($date . ' ' . $booked['end']);
                
                if ($slotStart < $bookedEnd && $slotEnd > $bookedStart) {
                    $isFree = false;
                    break;
                }
            }
            
            if ($isFree) {
                $available[] = [
                    'start' => $slotStart->format('H:i'),
                    'end' => $slotEnd->format('H:i')
                ];
            }
        }
        
        return $available;
    }
    
    /**
     * Termin im Google Calendar erstellen
     */
    /**
     * Event erstellen mit flexibler Signatur
     */
    public function createEvent($calendarId, $title, $description = '', $startDateTime = '', $endDateTime = '') {
        try {
            // Fallback für alte Signatur (wenn $title ein Array ist)
            if (is_array($title)) {
                $bookingData = $title;
                return $this->createEventFromBooking($calendarId, $bookingData);
            }
            
            // Neue Signatur: Einzelne Parameter
            $event = new Google_Service_Calendar_Event([
                'summary' => $title,
                'description' => $description,
                'start' => [
                    'dateTime' => date('c', strtotime($startDateTime)),
                    'timeZone' => 'Europe/Berlin',
                ],
                'end' => [
                    'dateTime' => date('c', strtotime($endDateTime)),
                    'timeZone' => 'Europe/Berlin',
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1 Tag vorher
                        ['method' => 'popup', 'minutes' => 60], // 1 Stunde vorher
                    ],
                ],
            ]);
            
            $createdEvent = $this->service->events->insert($calendarId, $event);
            return $createdEvent->getId();
            
        } catch (Exception $e) {
            logError('Google Calendar Event Erstellung fehlgeschlagen: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Event erstellen aus Buchungsdaten (alte Signatur)
     */
    private function createEventFromBooking($calendarId, $bookingData) {
        try {
            $event = new Google_Service_Calendar_Event([
                'summary' => 'Raumbuchung: ' . $bookingData['customer_name'],
                'description' => $this->formatEventDescription($bookingData),
                'start' => [
                    'dateTime' => $bookingData['booking_date'] . 'T' . $bookingData['start_time'] . ':00',
                    'timeZone' => 'Europe/Berlin',
                ],
                'end' => [
                    'dateTime' => $bookingData['booking_date'] . 'T' . $bookingData['end_time'] . ':00',
                    'timeZone' => 'Europe/Berlin',
                ],
                'attendees' => [
                    ['email' => $bookingData['customer_email'], 'displayName' => $bookingData['customer_name']]
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1 Tag vorher
                        ['method' => 'popup', 'minutes' => 60], // 1 Stunde vorher
                    ],
                ],
            ]);
            
            $event = $this->service->events->insert($calendarId, $event);
            
            return [
                'success' => true,
                'event_id' => $event->getId(),
                'event_link' => $event->getHtmlLink()
            ];
            
        } catch (Exception $e) {
            logError('Google Calendar Event Erstellung fehlgeschlagen: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Event aktualisieren
     */
    public function updateEvent($calendarId, $eventId, $title, $description, $startDateTime, $endDateTime) {
        try {
            // Event abrufen
            $event = $this->service->events->get($calendarId, $eventId);
            
            // Event aktualisieren
            $event->setSummary($title);
            $event->setDescription($description);
            $event->setStart(new Google_Service_Calendar_EventDateTime([
                'dateTime' => date('c', strtotime($startDateTime)),
                'timeZone' => 'Europe/Berlin',
            ]));
            $event->setEnd(new Google_Service_Calendar_EventDateTime([
                'dateTime' => date('c', strtotime($endDateTime)),
                'timeZone' => 'Europe/Berlin',
            ]));
            
            // Event speichern
            $updatedEvent = $this->service->events->update($calendarId, $eventId, $event);
            return $updatedEvent->getId();
            
        } catch (Exception $e) {
            logError('Google Calendar Event Update fehlgeschlagen: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Termin im Google Calendar löschen
     */
    public function deleteEvent($calendarId, $eventId) {
        try {
            $this->service->events->delete($calendarId, $eventId);
            return ['success' => true];
        } catch (Exception $e) {
            logError('Google Calendar Event Löschung fehlgeschlagen: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Termin-Beschreibung formatieren
     */
    private function formatEventDescription($bookingData) {
        $description = "Buchungs-ID: {$bookingData['booking_id']}\n";
        $description .= "Kunde: {$bookingData['customer_name']}\n";
        $description .= "E-Mail: {$bookingData['customer_email']}\n";
        
        if (!empty($bookingData['customer_phone'])) {
            $description .= "Telefon: {$bookingData['customer_phone']}\n";
        }
        
        if (!empty($bookingData['num_persons'])) {
            $description .= "Anzahl Personen: {$bookingData['num_persons']}\n";
        }
        
        $description .= "Mitglied: " . ($bookingData['is_member'] ? 'Ja' : 'Nein') . "\n";
        $description .= "Gesamtpreis: " . number_format($bookingData['total_price'], 2, ',', '.') . " €\n";
        
        if (!empty($bookingData['notes'])) {
            $description .= "\nAnmerkungen:\n{$bookingData['notes']}";
        }
        
        return $description;
    }
    
    /**
     * OAuth Authorization URL generieren
     */
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    /**
     * OAuth Code gegen Access Token tauschen
     */
    public function authenticate($code) {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }
        
        $tokenPath = __DIR__ . '/../credentials/token.json';
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($accessToken));
        
        return $accessToken;
    }
}
