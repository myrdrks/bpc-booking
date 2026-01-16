<?php
/**
 * API-Endpunkt für Buchungsdetails im Admin-Panel
 */
require_once __DIR__ . '/../config.php';

// Admin-Authentifizierung prüfen
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    jsonResponse(['error' => 'Nicht autorisiert'], 401);
}

$bookingId = $_GET['id'] ?? null;

if (!$bookingId) {
    jsonResponse(['error' => 'Buchungs-ID erforderlich'], 400);
}

try {
    $bookingModel = new Booking();
    $booking = $bookingModel->getBookingById($bookingId);
    
    if (!$booking) {
        jsonResponse(['error' => 'Buchung nicht gefunden'], 404);
    }
    
    $options = $bookingModel->getBookingOptions($bookingId);
    
    // HTML für Modal generieren
    $html = '<div class="booking-details">';
    $html .= '<h2>Buchungsdetails #' . str_pad($bookingId, 6, '0', STR_PAD_LEFT) . '</h2>';
    
    $html .= '<div class="detail-section">';
    $html .= '<h3>Rauminformationen</h3>';
    $html .= '<p><strong>Raum:</strong> ' . h($booking['room_name']) . '</p>';
    $html .= '<p><strong>Datum:</strong> ' . date('d.m.Y', strtotime($booking['booking_date'])) . '</p>';
    $html .= '<p><strong>Zeit:</strong> ' . substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5) . ' Uhr</p>';
    $html .= '</div>';
    
    $html .= '<div class="detail-section">';
    $html .= '<h3>Kundendaten</h3>';
    $html .= '<p><strong>Name:</strong> ' . h($booking['customer_name']) . '</p>';
    $html .= '<p><strong>E-Mail:</strong> ' . h($booking['customer_email']) . '</p>';
    if ($booking['customer_phone']) {
        $html .= '<p><strong>Telefon:</strong> ' . h($booking['customer_phone']) . '</p>';
    }
    if ($booking['num_persons']) {
        $html .= '<p><strong>Anzahl Personen:</strong> ' . $booking['num_persons'] . '</p>';
    }
    $html .= '<p><strong>Mitglied:</strong> ' . ($booking['is_member'] ? 'Ja' : 'Nein') . '</p>';
    $html .= '</div>';
    
    if (!empty($options)) {
        $html .= '<div class="detail-section">';
        $html .= '<h3>Gebuchte Extras</h3>';
        $html .= '<ul>';
        foreach ($options as $option) {
            $html .= '<li>' . h($option['name']);
            if ($option['quantity'] > 1) {
                $html .= ' (Anzahl: ' . $option['quantity'] . ')';
            }
            $html .= ' - ' . number_format($option['price'] * $option['quantity'], 2, ',', '.') . ' €</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    $html .= '<div class="detail-section">';
    $html .= '<h3>Preisübersicht</h3>';
    $html .= '<p><strong>Raumpreis:</strong> ' . number_format($booking['room_price'], 2, ',', '.') . ' €</p>';
    
    // Zeige Mitgliederrabatt falls vorhanden
    if (isset($booking['member_discount']) && $booking['member_discount'] > 0) {
        $html .= '<p><strong>Mitglieder-Rabatt:</strong> <span style="color:#28a745;">-' . number_format($booking['member_discount'], 2, ',', '.') . ' €</span></p>';
    }
    
    $html .= '<p><strong>Extras:</strong> ' . number_format($booking['options_price'], 2, ',', '.') . ' €</p>';
    $html .= '<p style="font-size:1.2em;color:#667eea;"><strong>Gesamtpreis:</strong> ' . number_format($booking['total_price'], 2, ',', '.') . ' €</p>';
    $html .= '</div>';
    
    if ($booking['notes']) {
        $html .= '<div class="detail-section">';
        $html .= '<h3>Anmerkungen</h3>';
        $html .= '<p>' . nl2br(h($booking['notes'])) . '</p>';
        $html .= '</div>';
    }
    
    $html .= '<div class="detail-section">';
    $html .= '<h3>Status</h3>';
    $html .= '<p><strong>Status:</strong> ' . ucfirst($booking['status']) . '</p>';
    $html .= '<p><strong>Erstellt am:</strong> ' . date('d.m.Y H:i', strtotime($booking['created_at'])) . ' Uhr</p>';
    if ($booking['confirmed_at']) {
        $html .= '<p><strong>Bestätigt am:</strong> ' . date('d.m.Y H:i', strtotime($booking['confirmed_at'])) . ' Uhr</p>';
    }
    if ($booking['confirmed_by']) {
        $html .= '<p><strong>Bestätigt von:</strong> ' . h($booking['confirmed_by']) . '</p>';
    }
    $html .= '</div>';
    
    $html .= '</div>';
    
    jsonResponse([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    logError('API Fehler: ' . $e->getMessage());
    jsonResponse(['error' => 'Ein Fehler ist aufgetreten'], 500);
}
