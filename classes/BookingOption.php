<?php
/**
 * BookingOption-Modell für Raumbuchungssystem
 */
class BookingOption {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Alle aktiven Optionen abrufen
     */
    public function getAllOptions() {
        $sql = "SELECT * FROM booking_options WHERE active = 1 ORDER BY category, name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Optionen nach Kategorie gruppiert abrufen
     */
    public function getOptionsByCategory($roomId = null) {
        if ($roomId) {
            // Optionen laden, die diesem Raum zugeordnet sind:
            // 1. Optionen die speziell diesem Raum zugewiesen sind (room_options)
            // 2. ODER Optionen die für alle Räume gelten (apply_to_rooms IS NULL)
            // 3. ODER Optionen die diesen Raum in apply_to_rooms enthalten
            $sql = "SELECT DISTINCT bo.* FROM booking_options bo
                    LEFT JOIN room_options ro ON bo.id = ro.option_id
                    WHERE bo.active = 1 
                    AND (
                        ro.room_id = ? 
                        OR bo.apply_to_rooms IS NULL 
                        OR FIND_IN_SET(?, bo.apply_to_rooms) > 0
                    )
                    ORDER BY bo.is_mandatory DESC, bo.category, bo.name ASC";
            $options = $this->db->fetchAll($sql, [$roomId, $roomId]);
        } else {
            $options = $this->getAllOptions();
        }
        
        $grouped = [];
        
        foreach ($options as $option) {
            $category = $option['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $option;
        }
        
        return $grouped;
    }
    
    /**
     * Option anhand ID abrufen
     */
    public function getOptionById($optionId) {
        $sql = "SELECT * FROM booking_options WHERE id = ? AND active = 1";
        return $this->db->fetchOne($sql, [$optionId]);
    }
    
    /**
     * Gesamtpreis für gewählte Optionen berechnen
     */
    public function calculateOptionsPrice($selectedOptions) {
        $total = 0;
        
        foreach ($selectedOptions as $optionData) {
            $option = $this->getOptionById($optionData['option_id']);
            if ($option) {
                $quantity = $optionData['quantity'] ?? 1;
                $total += $option['price'] * $quantity;
            }
        }
        
        return $total;
    }
}
