-- Migration: Email Templates Tabelle
-- Datum: 2025-11-11

CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Eindeutiger Template-Schlüssel',
    name VARCHAR(100) NOT NULL COMMENT 'Template-Name für Admin',
    subject VARCHAR(255) NOT NULL COMMENT 'E-Mail-Betreffzeile',
    body_html TEXT NOT NULL COMMENT 'HTML-Body mit Platzhaltern',
    available_variables TEXT COMMENT 'JSON: Verfügbare Variablen und Beschreibung',
    description TEXT COMMENT 'Beschreibung des Templates für Admin',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Editierbare E-Mail-Templates';

-- Standard-Templates einfügen
INSERT INTO email_templates (template_key, name, subject, body_html, available_variables, description) VALUES
('booking_confirmation', 
 'Buchungsanfrage erhalten', 
 'Ihre Buchungsanfrage wurde eingereicht',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #333F48; color: white; padding: 20px; text-align: center;">
        <h1>Buchungsanfrage erhalten</h1>
    </div>
    <div style="padding: 20px; background: #f8f9fa;">
        <p>Sehr geehrte/r {{customer_name}},</p>
        <p>vielen Dank für Ihre Buchungsanfrage. Wir haben Ihre Anfrage erhalten und werden diese schnellstmöglich prüfen.</p>
        <div style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #E35205;">
            <h3>Ihre Buchungsdetails:</h3>
            <p><strong>Buchungsnummer:</strong> {{booking_number}}</p>
            <p><strong>Raum:</strong> {{room_name}}</p>
            <p><strong>Datum:</strong> {{booking_date}}</p>
            <p><strong>Uhrzeit:</strong> {{start_time}} - {{end_time}} Uhr</p>
            <p><strong>Gesamtpreis:</strong> {{total_price}} €</p>
        </div>
        <p>Sie erhalten eine weitere E-Mail, sobald Ihre Buchung bestätigt wurde.</p>
        <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
        <p>Mit freundlichen Grüßen<br>Ihr Bremer Presse-Club Team</p>
    </div>
    <div style="text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em;">
        <p>Diese E-Mail wurde automatisch generiert.</p>
    </div>
</div>',
'{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "total_price": "Gesamtpreis", "num_persons": "Anzahl Personen"}',
'E-Mail die der Kunde erhält, wenn die Buchungsanfrage eingereicht wurde.'),

('booking_confirmed', 
 'Buchung bestätigt', 
 'Ihre Buchung wurde bestätigt!',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #28a745; color: white; padding: 20px; text-align: center;">
        <h1>✓ Buchung bestätigt!</h1>
    </div>
    <div style="padding: 20px; background: #f8f9fa;">
        <p>Sehr geehrte/r {{customer_name}},</p>
        <p>wir freuen uns, Ihnen mitteilen zu können, dass Ihre Buchung <strong>bestätigt</strong> wurde!</p>
        <div style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #28a745;">
            <h3>Ihre Buchungsdetails:</h3>
            <p><strong>Buchungsnummer:</strong> {{booking_number}}</p>
            <p><strong>Raum:</strong> {{room_name}}</p>
            <p><strong>Datum:</strong> {{booking_date}}</p>
            <p><strong>Uhrzeit:</strong> {{start_time}} - {{end_time}} Uhr</p>
            <p><strong>Anzahl Personen:</strong> {{num_persons}}</p>
            <p><strong>Gesamtpreis:</strong> {{total_price}} €</p>
        </div>
        <p>Wir freuen uns auf Ihren Besuch!</p>
        <p>Mit freundlichen Grüßen<br>Ihr Bremer Presse-Club Team</p>
    </div>
    <div style="text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em;">
        <p>Bei Fragen: <a href="mailto:booking@bremerpresseclub.de">booking@bremerpresseclub.de</a></p>
    </div>
</div>',
'{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "total_price": "Gesamtpreis", "num_persons": "Anzahl Personen"}',
'E-Mail die der Kunde erhält, wenn die Buchung vom Admin bestätigt wurde.'),

('booking_rejected', 
 'Buchung abgelehnt', 
 'Ihre Buchungsanfrage konnte nicht bestätigt werden',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #6F263D; color: white; padding: 20px; text-align: center;">
        <h1>Buchungsanfrage</h1>
    </div>
    <div style="padding: 20px; background: #f8f9fa;">
        <p>Sehr geehrte/r {{customer_name}},</p>
        <p>leider müssen wir Ihnen mitteilen, dass Ihre Buchungsanfrage für den <strong>{{booking_date}}</strong> nicht bestätigt werden konnte.</p>
        {{#if rejection_reason}}
        <div style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #6F263D;">
            <p><strong>Grund:</strong></p>
            <p>{{rejection_reason}}</p>
        </div>
        {{/if}}
        <p>Gerne können Sie einen alternativen Termin anfragen oder sich bei Fragen direkt an uns wenden.</p>
        <p>Mit freundlichen Grüßen<br>Ihr Bremer Presse-Club Team</p>
    </div>
    <div style="text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em;">
        <p>Bei Fragen: <a href="mailto:booking@bremerpresseclub.de">booking@bremerpresseclub.de</a></p>
    </div>
</div>',
'{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "rejection_reason": "Grund der Ablehnung (optional)"}',
'E-Mail die der Kunde erhält, wenn die Buchung abgelehnt wurde.'),

('admin_notification', 
 'Admin: Neue Buchungsanfrage', 
 'Neue Buchungsanfrage: {{room_name}} am {{booking_date}}',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #333F48; color: white; padding: 20px;">
        <h1>📋 Neue Buchungsanfrage</h1>
    </div>
    <div style="padding: 20px; background: #f8f9fa;">
        <p>Eine neue Buchungsanfrage wurde eingereicht und wartet auf Bestätigung.</p>
        <div style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #E35205;">
            <h3>Buchungsdetails:</h3>
            <p><strong>Buchungsnummer:</strong> {{booking_number}}</p>
            <p><strong>Raum:</strong> {{room_name}}</p>
            <p><strong>Datum:</strong> {{booking_date}}</p>
            <p><strong>Uhrzeit:</strong> {{start_time}} - {{end_time}} Uhr</p>
            <p><strong>Kunde:</strong> {{customer_name}}</p>
            <p><strong>E-Mail:</strong> {{customer_email}}</p>
            <p><strong>Telefon:</strong> {{customer_phone}}</p>
            <p><strong>Gesamtpreis:</strong> {{total_price}} €</p>
        </div>
        <p><a href="{{admin_url}}" style="display: inline-block; padding: 10px 20px; background: #E35205; color: white; text-decoration: none; border-radius: 4px;">Zur Admin-Ansicht</a></p>
    </div>
</div>',
'{"booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "customer_name": "Kundenname", "customer_email": "Kunden-E-Mail", "customer_phone": "Telefon", "total_price": "Gesamtpreis", "admin_url": "Link zum Admin-Bereich"}',
'E-Mail die der Administrator bei neuen Buchungsanfragen erhält.');
