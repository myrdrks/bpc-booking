<?php
/**
 * Buchungsbestätigungs-Seite
 */
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    header('Location: index.php');
    exit;
}

try {
    $bookingModel = new Booking();
    $booking = $bookingModel->getBookingByToken($token);
    
    if (!$booking) {
        throw new Exception('Buchung nicht gefunden');
    }
    
    $options = $bookingModel->getBookingOptions($booking['id']);
    
} catch (Exception $e) {
    die('Fehler: ' . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buchungsbestätigung</title>
    <link rel="stylesheet" href="assets/css/booking.css">
</head>
<body>
    <div class="booking-container success-page">
        <div class="success-icon">✓</div>
        
        <h1>Vielen Dank für Ihre Buchungsanfrage!</h1>
        
        <p class="success-message">
            Ihre Buchungsanfrage wurde erfolgreich übermittelt. 
            Sie erhalten in Kürze eine Bestätigungsmail an <strong><?= h($booking['customer_email']) ?></strong>.
        </p>
        
        <div class="booking-summary">
            <h2>Ihre Buchungsdetails</h2>
            
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="label">Buchungsnummer:</span>
                    <span class="value">#<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="label">Raum:</span>
                    <span class="value"><?= h($booking['room_name']) ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="label">Datum:</span>
                    <span class="value"><?= date('d.m.Y', strtotime($booking['booking_date'])) ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="label">Uhrzeit:</span>
                    <span class="value">
                        <?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?> Uhr
                    </span>
                </div>
                
                <div class="summary-item">
                    <span class="label">Name:</span>
                    <span class="value"><?= h($booking['customer_name']) ?></span>
                </div>
                
                <?php if ($booking['num_persons']): ?>
                <div class="summary-item">
                    <span class="label">Anzahl Personen:</span>
                    <span class="value"><?= $booking['num_persons'] ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-item">
                    <span class="label">Mitglied:</span>
                    <span class="value"><?= $booking['is_member'] ? 'Ja' : 'Nein' ?></span>
                </div>
                
                <?php if (!empty($options)): ?>
                <div class="summary-item full-width">
                    <span class="label">Gebuchte Extras:</span>
                    <ul class="options-list">
                        <?php foreach ($options as $option): ?>
                            <li>
                                <?= h($option['name']) ?>
                                <?php if ($option['quantity'] > 1): ?>
                                    (<?= $option['quantity'] ?>x)
                                <?php endif; ?>
                                - <?= number_format($option['price'] * $option['quantity'], 2, ',', '.') ?> €
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($booking['notes']): ?>
                <div class="summary-item full-width">
                    <span class="label">Anmerkungen:</span>
                    <p class="notes"><?= nl2br(h($booking['notes'])) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="summary-item total">
                    <span class="label">Gesamtpreis:</span>
                    <span class="value"><?= number_format($booking['total_price'], 2, ',', '.') ?> €</span>
                </div>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Wie geht es weiter?</h3>
            <ol>
                <li>Sie erhalten eine Bestätigungsmail mit allen Details</li>
                <li>Ein Administrator prüft Ihre Anfrage</li>
                <li>Nach Bestätigung wird der Termin im Kalender eingetragen</li>
                <li>Sie erhalten eine finale Bestätigung per E-Mail</li>
            </ol>
            
            <p class="status-badge status-<?= $booking['status'] ?>">
                Status: <?= ucfirst($booking['status']) ?>
            </p>
        </div>
        
        <div class="actions">
            <a href="index.php?room_id=<?= $booking['room_id'] ?>" class="btn btn-secondary">
                Weitere Buchung vornehmen
            </a>
        </div>
    </div>
</body>
</html>
