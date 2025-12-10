-- Migration: Settings Tabelle für Admin-Einstellungen
-- Datum: 2025-11-12

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Einstellungs-Schlüssel',
    setting_value TEXT COMMENT 'Einstellungs-Wert',
    description VARCHAR(255) COMMENT 'Beschreibung der Einstellung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System-Einstellungen';

-- Standard-Einstellungen einfügen (optional, werden sonst aus config.php gelesen)
INSERT INTO settings (setting_key, setting_value, description) VALUES
('admin_email', 'cl@noon.jetzt', 'E-Mail-Adresse für Admin-Benachrichtigungen'),
('admin_name', 'Administrator', 'Name des Administrators für E-Mails')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
