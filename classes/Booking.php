<?php
/**
 * Booking-Modell für Raumbuchungssystem
 */
class Booking {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Neue Buchung erstellen
     */
    public function createBooking($data) {
        $token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO bookings (
            room_id, booking_date, start_time, end_time,
            customer_name, customer_email, customer_phone,
            is_member, num_persons, room_price, options_price,
            total_price, notes, confirmation_token, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $params = [
            $data['room_id'],
            $data['booking_date'],
            $data['start_time'],
            $data['end_time'],
            $data['customer_name'],
            $data['customer_email'],
            $data['customer_phone'] ?? null,
            $data['is_member'] ? 1 : 0,
            $data['num_persons'] ?? null,
            $data['room_price'],
            $data['options_price'] ?? 0,
            $data['total_price'],
            $data['notes'] ?? null,
            $token
        ];
        
        $bookingId = $this->db->insert($sql, $params);
        
        // Log erstellen
        $this->addLog($bookingId, 'created', 'Buchung erstellt', $data['customer_email']);
        
        return [
            'booking_id' => $bookingId,
            'token' => $token
        ];
    }
    
    /**
     * Gebuchte Optionen speichern
     */
    public function addBookingOptions($bookingId, $options) {
        $sql = "INSERT INTO booking_options_selected (booking_id, option_id, quantity, price) 
                VALUES (?, ?, ?, ?)";
        
        foreach ($options as $option) {
            $this->db->query($sql, [
                $bookingId,
                $option['option_id'],
                $option['quantity'],
                $option['price']
            ]);
        }
    }
    
    /**
     * Buchung anhand ID abrufen
     */
    public function getBookingById($bookingId) {
        $sql = "SELECT b.*, r.name as room_name, r.google_calendar_id, r.manager_name, r.manager_email
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.id = ?";
        return $this->db->fetchOne($sql, [$bookingId]);
    }
    
    /**
     * Buchung anhand Token abrufen
     */
    public function getBookingByToken($token) {
        $sql = "SELECT b.*, r.name as room_name, r.google_calendar_id, r.manager_name, r.manager_email
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.confirmation_token = ?";
        return $this->db->fetchOne($sql, [$token]);
    }
    
    /**
     * Gebuchte Optionen abrufen
     */
    public function getBookingOptions($bookingId) {
        $sql = "SELECT bos.*, bo.name, bo.description, bo.category
                FROM booking_options_selected bos
                JOIN booking_options bo ON bos.option_id = bo.id
                WHERE bos.booking_id = ?";
        return $this->db->fetchAll($sql, [$bookingId]);
    }
    
    /**
     * Buchungsstatus ändern
     */
    public function updateStatus($bookingId, $status, $confirmedBy = null) {
        $sql = "UPDATE bookings SET status = ?, confirmed_at = ?, confirmed_by = ? 
                WHERE id = ?";
        $confirmedAt = in_array($status, ['confirmed', 'rejected']) ? date('Y-m-d H:i:s') : null;
        $this->db->query($sql, [$status, $confirmedAt, $confirmedBy, $bookingId]);
        
        $this->addLog($bookingId, 'status_changed', "Status geändert zu: {$status}", $confirmedBy);
    }
    
    /**
     * Google Event ID speichern
     */
    public function setGoogleEventId($bookingId, $eventId) {
        $sql = "UPDATE bookings SET google_event_id = ? WHERE id = ?";
        $this->db->query($sql, [$eventId, $bookingId]);
    }
    
    /**
     * Alle Buchungen für einen Raum an einem bestimmten Datum
     */
    public function getBookingsByRoomAndDate($roomId, $date) {
        $sql = "SELECT * FROM bookings 
                WHERE room_id = ? AND booking_date = ? AND status IN ('pending', 'confirmed')
                ORDER BY start_time ASC";
        return $this->db->fetchAll($sql, [$roomId, $date]);
    }
    
    /**
     * Buchungen nach Status abrufen
     */
    public function getBookingsByStatus($status = 'pending', $limit = 100) {
        $sql = "SELECT b.*, r.name as room_name
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.status = ?
                ORDER BY b.created_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$status, $limit]);
    }
    
    /**
     * Zeitüberschneidungen prüfen (inkl. Puffer)
     */
    public function checkTimeOverlap($roomId, $date, $startTime, $endTime, $excludeBookingId = null) {
        // Raum-Daten mit Puffer abrufen
        $roomModel = new Room();
        $room = $roomModel->getRoomById($roomId);
        
        $bufferBefore = (int)($room['buffer_before'] ?? 15);
        $bufferAfter = (int)($room['buffer_after'] ?? 15);
        
        // Zeiten mit Puffer erweitern
        $checkStart = date('H:i:s', strtotime($startTime . ' -' . $bufferBefore . ' minutes'));
        $checkEnd = date('H:i:s', strtotime($endTime . ' +' . $bufferAfter . ' minutes'));
        
        $sql = "SELECT COUNT(*) as count FROM bookings 
                WHERE room_id = ? 
                AND booking_date = ? 
                AND status IN ('pending', 'confirmed')
                AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND start_time < ?) OR
                    (end_time > ? AND end_time <= ?)
                )";
        
        $params = [$roomId, $date, $checkEnd, $checkStart, $checkStart, $checkEnd, $checkStart, $checkEnd];
        
        if ($excludeBookingId) {
            $sql .= " AND id != ?";
            $params[] = $excludeBookingId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Log-Eintrag hinzufügen
     */
    private function addLog($bookingId, $action, $description, $user = null) {
        $sql = "INSERT INTO booking_logs (booking_id, action, description, user, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $bookingId,
            $action,
            $description,
            $user,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
}
