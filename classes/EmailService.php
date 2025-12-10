<?php
/**
 * E-Mail Service für Raumbuchungssystem
 */

// Für SMTP-Support: composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $adminEmail;
    private $adminName;
    
    public function __construct() {
        if (SMTP_ENABLED && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->mailer = new PHPMailer(true);
            $this->configureSMTP();
        }
        
        // Admin-Email aus Datenbank laden (falls vorhanden)
        $this->loadAdminSettings();
    }
    
    /**
     * Admin-Einstellungen aus Datenbank laden
     */
    private function loadAdminSettings() {
        try {
            $db = Database::getInstance();
            $adminEmail = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'admin_email'");
            $adminName = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'admin_name'");
            
            $this->adminEmail = $adminEmail['setting_value'] ?? ADMIN_EMAIL;
            $this->adminName = $adminName['setting_value'] ?? ADMIN_NAME;
        } catch (Exception $e) {
            // Fallback auf config.php wenn Tabelle nicht existiert
            $this->adminEmail = ADMIN_EMAIL;
            $this->adminName = ADMIN_NAME;
        }
    }
    
    /**
     * SMTP konfigurieren
     */
    private function configureSMTP() {
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = SMTP_AUTH;
        $this->mailer->Username = SMTP_USERNAME;
        $this->mailer->Password = SMTP_PASSWORD;
        $this->mailer->SMTPSecure = SMTP_SECURE;
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->CharSet = 'UTF-8';
        
        $this->mailer->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    }
    
    /**
     * E-Mail senden
     */
    private function send($to, $toName, $subject, $body, $isHTML = true) {
        try {
            if ($this->mailer) {
                // PHPMailer verwenden
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($to, $toName);
                $this->mailer->isHTML($isHTML);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                
                if ($isHTML) {
                    $this->mailer->AltBody = strip_tags($body);
                }
                
                return $this->mailer->send();
            } else {
                // Fallback: PHP mail()
                $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
                $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                
                if ($isHTML) {
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                } else {
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                }
                
                return mail($to, $subject, $body, $headers);
            }
        } catch (Exception $e) {
            logError('E-Mail-Versand fehlgeschlagen: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buchungsbestätigung an Kunden senden
     */
    public function sendBookingConfirmation($bookingId) {
        $bookingModel = new Booking();
        $booking = $bookingModel->getBookingById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $variables = [
            'customer_name' => $booking['customer_name'],
            'booking_number' => str_pad($booking['id'], 6, '0', STR_PAD_LEFT),
            'room_name' => $booking['room_name'],
            'booking_date' => date('d.m.Y', strtotime($booking['booking_date'])),
            'start_time' => substr($booking['start_time'], 0, 5),
            'end_time' => substr($booking['end_time'], 0, 5),
            'total_price' => number_format($booking['total_price'], 2, ',', '.'),
            'num_persons' => $booking['num_persons']
        ];
        
        $template = $this->renderTemplate('booking_confirmation', $variables);
        
        return $this->send(
            $booking['customer_email'],
            $booking['customer_name'],
            $template['subject'],
            $template['body'],
            true
        );
    }
    
    /**
     * Admin-Benachrichtigung senden
     */
    public function sendAdminNotification($bookingId) {
        $bookingModel = new Booking();
        $booking = $bookingModel->getBookingById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $subject = 'Neue Buchungsanfrage #' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
        $body = $this->getAdminNotificationTemplate($booking);
        
        // Wenn Raummanager hinterlegt ist, nur an diesen senden
        // Ansonsten an zentralen Admin
        if (!empty($booking['manager_email'])) {
            $managerName = $booking['manager_name'] ?? 'Raummanager';
            return $this->send(
                $booking['manager_email'],
                $managerName,
                $subject,
                $body,
                true
            );
        } else {
            return $this->send(
                $this->adminEmail,
                $this->adminName,
                $subject,
                $body,
                true
            );
        }
    }
    
    /**
     * Buchung bestätigt - E-Mail an Kunden
     */
    public function sendBookingConfirmed($bookingId) {
        $bookingModel = new Booking();
        $booking = $bookingModel->getBookingById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $variables = [
            'customer_name' => $booking['customer_name'],
            'booking_number' => str_pad($booking['id'], 6, '0', STR_PAD_LEFT),
            'room_name' => $booking['room_name'],
            'booking_date' => date('d.m.Y', strtotime($booking['booking_date'])),
            'start_time' => substr($booking['start_time'], 0, 5),
            'end_time' => substr($booking['end_time'], 0, 5),
            'total_price' => number_format($booking['total_price'], 2, ',', '.'),
            'num_persons' => $booking['num_persons']
        ];
        
        $template = $this->renderTemplate('booking_confirmed', $variables);
        
        return $this->send(
            $booking['customer_email'],
            $booking['customer_name'],
            $template['subject'],
            $template['body'],
            true
        );
    }
    
    /**
     * Buchung abgelehnt - E-Mail an Kunden
     */
    public function sendBookingRejected($bookingId, $reason = '') {
        $bookingModel = new Booking();
        $booking = $bookingModel->getBookingById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $variables = [
            'customer_name' => $booking['customer_name'],
            'booking_number' => str_pad($booking['id'], 6, '0', STR_PAD_LEFT),
            'room_name' => $booking['room_name'],
            'booking_date' => date('d.m.Y', strtotime($booking['booking_date'])),
            'rejection_reason' => $reason
        ];
        
        $template = $this->renderTemplate('booking_rejected', $variables);
        
        return $this->send(
            $booking['customer_email'],
            $booking['customer_name'],
            $template['subject'],
            $template['body'],
            true
        );
    }
    
    /**
     * Template aus Datenbank laden und Variablen ersetzen
     */
    private function renderTemplate($templateKey, $variables) {
        $db = Database::getInstance();
        $template = $db->fetchOne(
            "SELECT subject, body_html FROM email_templates WHERE template_key = ? AND active = 1",
            [$templateKey]
        );
        
        if (!$template) {
            logError("Email template not found: $templateKey");
            return ['subject' => 'Buchungsinformation', 'body' => 'Template nicht gefunden.'];
        }
        
        $subject = $template['subject'];
        $body = $template['body_html'];
        
        // Variablen ersetzen
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }
        
        // Bedingte Blöcke verarbeiten (einfache if-Logik)
        $body = preg_replace_callback('/{{#if\s+(\w+)}}(.*?){{\/if}}/s', function($matches) use ($variables) {
            $var = $matches[1];
            $content = $matches[2];
            return (!empty($variables[$var])) ? $content : '';
        }, $body);
        
        return ['subject' => $subject, 'body' => $body];
    }
    
    /**
     * Template: Buchungsbestätigung
     */
    private function getBookingConfirmationTemplate($booking) {
        $bookingNumber = str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
        $date = date('d.m.Y', strtotime($booking['booking_date']));
        $startTime = substr($booking['start_time'], 0, 5);
        $endTime = substr($booking['end_time'], 0, 5);
        $price = number_format($booking['total_price'], 2, ',', '.');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #3498db; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 0.9em; }
                .label { font-weight: bold; color: #2c3e50; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Buchungsanfrage erhalten</h1>
                </div>
                
                <div class='content'>
                    <p>Sehr geehrte/r {$booking['customer_name']},</p>
                    
                    <p>vielen Dank für Ihre Buchungsanfrage. Wir haben Ihre Anfrage erhalten und werden diese schnellstmöglich prüfen.</p>
                    
                    <div class='details'>
                        <h3>Ihre Buchungsdetails:</h3>
                        <p><span class='label'>Buchungsnummer:</span> #{$bookingNumber}</p>
                        <p><span class='label'>Raum:</span> {$booking['room_name']}</p>
                        <p><span class='label'>Datum:</span> {$date}</p>
                        <p><span class='label'>Uhrzeit:</span> {$startTime} - {$endTime} Uhr</p>
                        <p><span class='label'>Gesamtpreis:</span> {$price} €</p>
                    </div>
                    
                    <p>Sie erhalten eine weitere E-Mail, sobald Ihre Buchung bestätigt wurde.</p>
                    
                    <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
                    
                    <p>Mit freundlichen Grüßen<br>Ihr Buchungsteam</p>
                </div>
                
                <div class='footer'>
                    <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template: Admin-Benachrichtigung
     */
    private function getAdminNotificationTemplate($booking) {
        $bookingNumber = str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
        $date = date('d.m.Y', strtotime($booking['booking_date']));
        $startTime = substr($booking['start_time'], 0, 5);
        $endTime = substr($booking['end_time'], 0, 5);
        $price = number_format($booking['total_price'], 2, ',', '.');
        $adminUrl = ADMIN_URL;
        
        $optionsModel = new Booking();
        $options = $optionsModel->getBookingOptions($booking['id']);
        
        $optionsHtml = '';
        if (!empty($options)) {
            $optionsHtml = '<h4>Gebuchte Extras:</h4><ul>';
            foreach ($options as $option) {
                $optionsHtml .= '<li>' . h($option['name']) . ' (Anzahl: ' . $option['quantity'] . ')</li>';
            }
            $optionsHtml .= '</ul>';
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .details { background: white; padding: 15px; margin: 15px 0; }
                .button { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                .label { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Neue Buchungsanfrage</h1>
                </div>
                
                <div class='content'>
                    <p>Eine neue Buchungsanfrage wurde eingereicht und wartet auf Bestätigung.</p>
                    
                    <div class='details'>
                        <p><span class='label'>Buchungsnummer:</span> #{$bookingNumber}</p>
                        <p><span class='label'>Raum:</span> {$booking['room_name']}</p>
                        <p><span class='label'>Datum:</span> {$date}</p>
                        <p><span class='label'>Uhrzeit:</span> {$startTime} - {$endTime} Uhr</p>
                        <hr>
                        <p><span class='label'>Kunde:</span> {$booking['customer_name']}</p>
                        <p><span class='label'>E-Mail:</span> {$booking['customer_email']}</p>
                        <p><span class='label'>Telefon:</span> {$booking['customer_phone']}</p>
                        <p><span class='label'>Anzahl Personen:</span> {$booking['num_persons']}</p>
                        <p><span class='label'>Mitglied:</span> " . ($booking['is_member'] ? 'Ja' : 'Nein') . "</p>
                        <hr>
                        {$optionsHtml}
                        <p><span class='label'>Gesamtpreis:</span> {$price} €</p>
                        
                        " . (!empty($booking['notes']) ? "<p><span class='label'>Anmerkungen:</span><br>" . nl2br(h($booking['notes'])) . "</p>" : "") . "
                    </div>
                    
                    <a href='{$adminUrl}' class='button'>Zum Admin-Bereich</a>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template: Buchung bestätigt
     */
    private function getBookingConfirmedTemplate($booking) {
        $bookingNumber = str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
        $date = date('d.m.Y', strtotime($booking['booking_date']));
        $startTime = substr($booking['start_time'], 0, 5);
        $endTime = substr($booking['end_time'], 0, 5);
        $price = number_format($booking['total_price'], 2, ',', '.');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #28a745; }
                .label { font-weight: bold; color: #2c3e50; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Buchung bestätigt!</h1>
                </div>
                
                <div class='content'>
                    <p>Sehr geehrte/r {$booking['customer_name']},</p>
                    
                    <p>wir freuen uns, Ihnen mitteilen zu können, dass Ihre Buchung bestätigt wurde!</p>
                    
                    <div class='details'>
                        <h3>Ihre Buchungsdetails:</h3>
                        <p><span class='label'>Buchungsnummer:</span> #{$bookingNumber}</p>
                        <p><span class='label'>Raum:</span> {$booking['room_name']}</p>
                        <p><span class='label'>Datum:</span> {$date}</p>
                        <p><span class='label'>Uhrzeit:</span> {$startTime} - {$endTime} Uhr</p>
                        <p><span class='label'>Gesamtpreis:</span> {$price} €</p>
                    </div>
                    
                    <p>Der Termin wurde in unserem Kalender eingetragen.</p>
                    
                    <p>Wir freuen uns auf Ihren Besuch!</p>
                    
                    <p>Mit freundlichen Grüßen<br>Ihr Buchungsteam</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template: Buchung abgelehnt
     */
    private function getBookingRejectedTemplate($booking, $reason) {
        $bookingNumber = str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
        $date = date('d.m.Y', strtotime($booking['booking_date']));
        
        $reasonHtml = '';
        if (!empty($reason)) {
            $reasonHtml = "<p><strong>Grund:</strong><br>" . nl2br(h($reason)) . "</p>";
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .details { background: white; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Buchungsanfrage</h1>
                </div>
                
                <div class='content'>
                    <p>Sehr geehrte/r {$booking['customer_name']},</p>
                    
                    <p>leider können wir Ihre Buchungsanfrage #{$bookingNumber} für {$booking['room_name']} am {$date} nicht bestätigen.</p>
                    
                    {$reasonHtml}
                    
                    <p>Bei Fragen oder für alternative Terminvorschläge stehen wir Ihnen gerne zur Verfügung.</p>
                    
                    <p>Mit freundlichen Grüßen<br>Ihr Buchungsteam</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
