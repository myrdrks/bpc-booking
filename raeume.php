<?php
/**
 * Raumübersicht - Startseite mit allen verfügbaren Räumen
 */
require_once __DIR__ . '/config.php';

try {
    $roomModel = new Room();
    $rooms = $roomModel->getAllRooms();
} catch (Exception $e) {
    die('Fehler: ' . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raumbuchung - Bremer Presse-Club</title>
    <link rel="stylesheet" href="assets/css/booking.css">
    <style>
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .room-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
        }
        
        .room-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .room-card-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .room-card-header .capacity {
            margin-top: 0.5rem;
            opacity: 0.9;
        }
        
        .room-card-body {
            padding: 2rem;
        }
        
        .room-description {
            color: #6c757d;
            margin-bottom: 1.5rem;
            min-height: 80px;
        }
        
        .room-pricing {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .room-pricing h4 {
            margin: 0 0 0.75rem 0;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.95rem;
        }
        
        .price-highlight {
            color: #667eea;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .room-features {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .room-features li {
            padding: 0.4rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .room-features li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .book-button {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .book-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <header class="booking-header">
            <h1>Raumbuchung im Bremer Presse-Club</h1>
            <p class="room-description">Wählen Sie den passenden Raum für Ihre Veranstaltung</p>
        </header>

        <div class="rooms-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card" onclick="location.href='index.php?room_id=<?= $room['id'] ?>'">
                    <div class="room-card-header">
                        <h2><?= h($room['name']) ?></h2>
                        <div class="capacity">
                            👥 Bis zu <?= $room['capacity'] ?> Personen
                        </div>
                    </div>
                    
                    <div class="room-card-body">
                        <p class="room-description"><?= h($room['description']) ?></p>
                        
                        <div class="room-pricing">
                            <h4>Preise:</h4>
                            
                            <?php if ($room['name'] === 'CLUB27'): ?>
                                <div class="price-row">
                                    <span>Mitglieder:</span>
                                    <span class="price-highlight">0 € Raummiete*</span>
                                </div>
                                <div class="price-row">
                                    <span>Nicht-Mitglieder:</span>
                                    <span class="price-highlight"><?= number_format($room['price_non_member'], 0, ',', '.') ?> €</span>
                                </div>
                                <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                                    *zzgl. Servicepauschale 250€
                                </small>
                                
                            <?php elseif ($room['name'] === 'Tagungsraum' || $room['name'] === 'Club-Lounge'): ?>
                                <div class="price-row">
                                    <span>Mitglieder:</span>
                                    <span class="price-highlight">Kostenfrei ✨</span>
                                </div>
                                <div class="price-row">
                                    <span>Nicht-Mitglieder:</span>
                                    <span class="price-highlight">100 € / 1. Std.</span>
                                </div>
                                <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                                    Jede weitere Stunde: 50€
                                </small>
                                
                            <?php else: ?>
                                <div class="price-row">
                                    <span>Mitglieder:</span>
                                    <span class="price-highlight"><?= number_format($room['price_member'], 2, ',', '.') ?> €</span>
                                </div>
                                <div class="price-row">
                                    <span>Nicht-Mitglieder:</span>
                                    <span class="price-highlight"><?= number_format($room['price_non_member'], 2, ',', '.') ?> €</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php
                        // Raum-spezifische Features
                        $features = [];
                        if ($room['name'] === 'CLUB27') {
                            $features = [
                                'Bar mit Küche & Klavier',
                                'Außenterrasse',
                                'Profi-Raumbeschallung',
                                'Beamer & Leinwand'
                            ];
                        } elseif ($room['name'] === 'Tagungsraum') {
                            $features = [
                                'Technik & Workshopkoffer inkl.',
                                'Highspeed-WLAN',
                                'Flipchart & Moderationsmaterial',
                                'Getränke per Selbstabrechnung'
                            ];
                        } elseif ($room['name'] === 'Club-Lounge') {
                            $features = [
                                'Gemütliche Lounge-Atmosphäre',
                                '60-Zoll-Fernseher',
                                'Plattenspieler',
                                'Getränkekühlschrank'
                            ];
                        }
                        ?>
                        
                        <?php if (!empty($features)): ?>
                            <ul class="room-features">
                                <?php foreach ($features as $feature): ?>
                                    <li><?= h($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <button class="book-button">
                            Jetzt buchen
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="info-box" style="margin-top: 3rem;">
            <h3>Mitglied werden und sparen!</h3>
            <p>Als Mitglied des Bremer Presse-Clubs profitieren Sie von erheblichen Preisvorteilen:</p>
            <ul>
                <li><strong>CLUB27:</strong> Keine Raummiete (Ersparnis: 500€)</li>
                <li><strong>Tagungsraum & Club-Lounge:</strong> Kostenfreie Nutzung</li>
                <li>Alle technischen Geräte inklusive</li>
                <li>Viele weitere Vorteile</li>
            </ul>
            <p style="margin-top: 1rem;">
                <a href="#mitgliedschaft" class="btn btn-primary">Mehr über die Mitgliedschaft erfahren</a>
            </p>
        </div>
    </div>
</body>
</html>
