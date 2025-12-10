<?php
/**
 * Fix: E-Mail Templates Encoding korrigieren (PRODUCTION)
 * 
 * ANLEITUNG:
 * 1. Diese Datei auf den IONOS-Server hochladen
 * 2. Im Browser aufrufen: https://deine-domain.de/buchung/fix-email-templates-encoding-production.php
 * 3. Nach erfolgreicher Ausführung SOFORT LÖSCHEN!
 * 
 * ACHTUNG: Dieses Script sollte nur einmal ausgeführt werden!
 */

// Sicherheitscheck: Nur für Admins
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('❌ Zugriff verweigert. Bitte als Admin einloggen.');
}

require_once __DIR__ . '/config.php';

// Output buffer für saubere Ausgabe
ob_start();

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<html><head><meta charset='UTF-8'><title>Email Templates Encoding Fix</title></head><body>";
    echo "<h1>🔧 E-Mail Templates Encoding korrigieren</h1>";
    echo "<pre>";
    
    // Prüfen ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'email_templates'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception("Tabelle 'email_templates' existiert nicht! Bitte zuerst Migration 07 ausführen.");
    }
    
    echo "✓ Tabelle email_templates gefunden\n\n";
    
    // Alte Daten prüfen
    $oldData = $pdo->query("SELECT template_key, LEFT(description, 50) as description FROM email_templates")->fetchAll();
    echo "Aktuelle Daten (vor Fix):\n";
    foreach ($oldData as $row) {
        echo "  - {$row['template_key']}: {$row['description']}...\n";
    }
    echo "\n";
    
    // Alte Daten löschen
    $pdo->exec("DELETE FROM email_templates");
    echo "✓ Alte Templates gelöscht\n\n";
    
    // Templates mit korrekter UTF-8 Kodierung einfügen
    $templates = [
        [
            'template_key' => 'booking_confirmation',
            'name' => 'Buchungsanfrage erhalten',
            'subject' => 'Ihre Buchungsanfrage wurde eingereicht',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
            'available_variables' => '{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "total_price": "Gesamtpreis", "num_persons": "Anzahl Personen"}',
            'description' => 'E-Mail die der Kunde erhält, wenn die Buchungsanfrage eingereicht wurde.'
        ],
        [
            'template_key' => 'booking_confirmed',
            'name' => 'Buchung bestätigt',
            'subject' => 'Ihre Buchung wurde bestätigt!',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
            'available_variables' => '{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "total_price": "Gesamtpreis", "num_persons": "Anzahl Personen"}',
            'description' => 'E-Mail die der Kunde erhält, wenn die Buchung vom Admin bestätigt wurde.'
        ],
        [
            'template_key' => 'booking_rejected',
            'name' => 'Buchung abgelehnt',
            'subject' => 'Ihre Buchungsanfrage konnte nicht bestätigt werden',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
            'available_variables' => '{"customer_name": "Name des Kunden", "booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "rejection_reason": "Grund der Ablehnung (optional)"}',
            'description' => 'E-Mail die der Kunde erhält, wenn die Buchung abgelehnt wurde.'
        ],
        [
            'template_key' => 'admin_notification',
            'name' => 'Admin: Neue Buchungsanfrage',
            'subject' => 'Neue Buchungsanfrage: {{room_name}} am {{booking_date}}',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
            'available_variables' => '{"booking_number": "Buchungsnummer", "room_name": "Raumname", "booking_date": "Buchungsdatum", "start_time": "Startzeit", "end_time": "Endzeit", "customer_name": "Kundenname", "customer_email": "Kunden-E-Mail", "customer_phone": "Telefon", "total_price": "Gesamtpreis", "admin_url": "Link zum Admin-Bereich"}',
            'description' => 'E-Mail die der Administrator bei neuen Buchungsanfragen erhält.'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO email_templates 
        (template_key, name, subject, body_html, available_variables, description) 
        VALUES 
        (:template_key, :name, :subject, :body_html, :available_variables, :description)
    ");
    
    foreach ($templates as $template) {
        $stmt->execute($template);
        echo "✓ Template '{$template['template_key']}' eingefügt\n";
    }
    
    echo "\n✅ Encoding erfolgreich korrigiert!\n\n";
    
    // Neue Daten anzeigen
    echo "Neue Daten (nach Fix):\n";
    $newData = $pdo->query("SELECT template_key, description FROM email_templates")->fetchAll();
    foreach ($newData as $row) {
        echo "  ✓ {$row['template_key']}: {$row['description']}\n";
    }
    
    echo "\n</pre>";
    echo "<h2 style='color: green;'>✅ Erfolgreich abgeschlossen!</h2>";
    echo "<p><strong style='color: red;'>WICHTIG:</strong> Lösche diese Datei jetzt sofort vom Server!</p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "</pre></body></html>";
    exit(1);
}

ob_end_flush();
