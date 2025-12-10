<?php
/**
 * Room-Modell für Raumbuchungssystem
 */
class Room {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Raum anhand ID abrufen
     */
    public function getRoomById($roomId) {
        $sql = "SELECT * FROM rooms WHERE id = ?";
        return $this->db->fetchOne($sql, [$roomId]);
    }
    
    /**
     * Alle aktiven Räume abrufen
     */
    public function getAllRooms() {
        $sql = "SELECT * FROM rooms ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Raum anhand Google Calendar ID finden
     */
    public function getRoomByCalendarId($calendarId) {
        $sql = "SELECT * FROM rooms WHERE google_calendar_id = ?";
        return $this->db->fetchOne($sql, [$calendarId]);
    }
    
    /**
     * Preis für Raum berechnen (Mitglied vs. Nicht-Mitglied)
     */
    public function getRoomPrice($roomId, $isMember = false) {
        $room = $this->getRoomById($roomId);
        if (!$room) {
            return 0;
        }
        return $isMember ? $room['price_member'] : $room['price_non_member'];
    }
}
