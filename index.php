<?php
/**
 * Hauptbuchungsformular - Index-Seite
 * Diese Datei kann für jeden Raum angepasst werden
 */
require_once __DIR__ . '/config.php';

// Raum-ID aus URL Parameter oder Konfiguration
$roomId = $_GET['room_id'] ?? 1;

try {
    $roomModel = new Room();
    $room = $roomModel->getRoomById($roomId);
    
    if (!$room) {
        throw new Exception('Raum nicht gefunden');
    }
    
    $optionModel = new BookingOption();
    $optionsByCategory = $optionModel->getOptionsByCategory($roomId);
    
} catch (Exception $e) {
    die('Fehler: ' . h($e->getMessage()));
}

// Standard: Nicht-Mitglied
$isMember = isset($_GET['member']) ? (bool)$_GET['member'] : false;
$roomPrice = $room[$isMember ? 'price_member' : 'price_non_member'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raumbuchung - <?= h($room['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Andada+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/booking.css">
</head>
<body>
    <div class="booking-container">

        <?php if (isset($_SESSION['booking_errors']) && !empty($_SESSION['booking_errors'])): ?>
            <div class="alert alert-error">
                <h3>Fehler bei der Buchung:</h3>
                <ul>
                    <?php foreach ($_SESSION['booking_errors'] as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['booking_errors']); ?>
        <?php endif; ?>

        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Datum</div>
            </div>
            <div class="progress-step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Uhrzeit</div>
            </div>
            <div class="progress-step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Ihre Daten</div>
            </div>
            <div class="progress-step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Extras</div>
            </div>
            <div class="progress-step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-label">Zusammenfassung</div>
            </div>
        </div>

        <form id="booking-form" action="process-booking.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
            <input type="hidden" id="booking_date" name="booking_date" value="">
            <input type="hidden" id="room_price" name="room_price" value="<?= $roomPrice ?>">
            <input type="hidden" id="options_price" name="options_price" value="0">
            <input type="hidden" id="total_price" name="total_price" value="<?= $roomPrice ?>">

            <!-- Schritt 1: Datum auswählen -->
            <section class="booking-section step-content" data-step="1" style="display: block;">
                <h2>1. Wählen Sie ein Datum</h2>
                <div id="calendar-container"></div>
                <div class="step-navigation">
                    <button type="button" class="btn btn-primary btn-next" onclick="nextStep(1)">Weiter →</button>
                </div>
            </section>

            <!-- Schritt 2: Zeit auswählen -->
            <section class="booking-section step-content" data-step="2" style="display: none;">
                <h2>2. Wählen Sie einen Zeitraum</h2>
                <div id="time-slots"></div>
                
                <div class="custom-time-input">
                    <h3>Oder geben Sie eine individuelle Zeit ein:</h3>
                    <div class="time-inputs">
                        <div class="form-group">
                            <label for="start_time">Von:</label>
                            <input type="time" 
                                   id="start_time" 
                                   name="start_time" 
                                   min="08:00" 
                                   max="23:59" 
                                   step="900"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Bis:</label>
                            <input type="time" 
                                   id="end_time" 
                                   name="end_time" 
                                   min="08:00" 
                                   max="23:59" 
                                   step="900"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="step-navigation">
                    <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep(2)">← Zurück</button>
                    <button type="button" class="btn btn-primary btn-next" onclick="nextStep(2)">Weiter →</button>
                </div>
            </section>

            <!-- Schritt 3: Persönliche Daten -->
            <section class="booking-section step-content" data-step="3" style="display: none;">
                <h2>3. Ihre Daten</h2>
                
                <div class="form-group">
                    <label for="customer_name">Name *</label>
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           required>
                </div>

                <div class="form-group">
                    <label for="customer_email">E-Mail *</label>
                    <input type="email" 
                           id="customer_email" 
                           name="customer_email" 
                           required>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Telefon</label>
                    <input type="tel" 
                           id="customer_phone" 
                           name="customer_phone">
                </div>

                <div class="form-group">
                    <label for="num_persons">Anzahl Personen *</label>
                    <input type="number" 
                           id="num_persons" 
                           name="num_persons" 
                           min="1" 
                           max="<?= $room['capacity'] ?>"
                           placeholder="Max. <?= $room['capacity'] ?> Personen"
                           required>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" 
                           id="is_member" 
                           name="is_member" 
                           value="1"
                           <?= $isMember ? 'checked' : '' ?>>
                    <label for="is_member">Ich bin Mitglied</label>
                </div>
                <div class="step-navigation">
                    <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep(3)">← Zurück</button>
                    <button type="button" class="btn btn-primary btn-next" onclick="nextStep(3)">Weiter →</button>
                </div>
            </section>

            <!-- Schritt 4: Extras auswählen -->
            <section class="booking-section step-content" data-step="4" style="display: none;">
                <h2>4. Extras hinzufügen (optional)</h2>
                
                <?php 
                $categoryNames = [
                    'beverages' => 'Getränke',
                    'food' => 'Verpflegung',
                    'equipment' => 'Ausstattung',
                    'service' => 'Service'
                ];
                
                foreach ($optionsByCategory as $category => $options): 
                ?>
                    <div class="options-category">
                        <h3><?= h($categoryNames[$category] ?? $category) ?></h3>
                        <div class="options-grid">
                            <?php foreach ($options as $option): ?>
                                <div class="option-item <?= $option['is_mandatory'] ? 'mandatory-option' : '' ?>">
                                    <input type="checkbox" 
                                           class="option-checkbox"
                                           id="option_<?= $option['id'] ?>" 
                                           name="options[]" 
                                           value="<?= $option['id'] ?>"
                                           data-price="<?= $option['price'] ?>"
                                           data-allow-quantity="<?= $option['allow_quantity'] ?>"
                                           data-min-quantity="<?= $option['min_quantity'] ?? 1 ?>"
                                           data-is-mandatory="<?= $option['is_mandatory'] ?>"
                                           <?= $option['is_mandatory'] ? 'checked disabled' : '' ?>>
                                    <label for="option_<?= $option['id'] ?>">
                                        <strong><?= h($option['name']) ?><?= $option['is_mandatory'] ? ' <span style="color: #6F263D;">(Pflicht)</span>' : '' ?></strong>
                                        <span class="option-price"><?= number_format($option['price'], 2, ',', '.') ?> €</span>
                                        <?php if ($option['description']): ?>
                                            <small><?= h($option['description']) ?></small>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php if ($option['allow_quantity']): ?>
                                    <div class="option-quantity" id="quantity_wrapper_<?= $option['id'] ?>" style="display:none;">
                                        <label for="quantity_<?= $option['id'] ?>">Anzahl<?= ($option['min_quantity'] > 1) ? ' (mind. ' . $option['min_quantity'] . ')' : '' ?>:</label>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <input type="number" 
                                                   id="quantity_<?= $option['id'] ?>" 
                                                   name="option_quantities[<?= $option['id'] ?>]" 
                                                   min="<?= $option['min_quantity'] ?? 1 ?>" 
                                                   value="<?= $option['min_quantity'] ?? 1 ?>"
                                                   class="option-quantity-input"
                                                   disabled
                                                   style="flex: 1;">
                                            <button type="button" 
                                                    class="set-all-persons-btn" 
                                                    data-option-id="<?= $option['id'] ?>"
                                                    data-min-quantity="<?= $option['min_quantity'] ?? 1 ?>"
                                                    style="padding: 8px 12px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; white-space: nowrap;">
                                                Alle
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($option['is_mandatory']): ?>
                                    <!-- Hidden input für Pflichtoptionen, da disabled checkboxes nicht übermittelt werden -->
                                    <input type="hidden" name="options[]" value="<?= $option['id'] ?>">
                                    <?php if ($option['allow_quantity']): ?>
                                    <input type="hidden" name="option_quantities[<?= $option['id'] ?>]" value="<?= $option['min_quantity'] ?? 1 ?>">
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="step-navigation">
                    <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep(4)">← Zurück</button>
                    <button type="button" class="btn btn-primary btn-next" onclick="nextStep(4)">Weiter →</button>
                </div>
            </section>

            <!-- Schritt 5: Zusammenfassung und Anmerkungen -->
            <section class="booking-section step-content" data-step="5" style="display: none;">
                <h2>5. Zusammenfassung & Bestätigung</h2>
                
                <!-- Buchungsübersicht -->
                <div class="booking-summary-box">
                    <h3>Ihre Buchung im Überblick:</h3>
                    <div class="summary-item">
                        <strong>Raum:</strong> <span id="summary-room"><?= h($room['name']) ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>Datum:</strong> <span id="summary-date">-</span>
                    </div>
                    <div class="summary-item">
                        <strong>Uhrzeit:</strong> <span id="summary-time">-</span>
                    </div>
                    <div class="summary-item" id="summary-buffer-wrapper" style="display: none; background: #fff5f0; padding: 0.75rem; border-radius: 4px; border-left: 3px solid #E35205;">
                        <strong>⏱️ Gesperrte Zeit (inkl. Puffer):</strong> <span id="summary-buffer">-</span>
                        <br><small style="color: #6F263D;">Der Raum ist inkl. Vor- und Nachbereitung von <span id="summary-buffer-time">-</span> gesperrt.</small>
                    </div>
                    <div class="summary-item">
                        <strong>Name:</strong> <span id="summary-name">-</span>
                    </div>
                    <div class="summary-item">
                        <strong>E-Mail:</strong> <span id="summary-email">-</span>
                    </div>
                    <div class="summary-item">
                        <strong>Telefon:</strong> <span id="summary-phone">-</span>
                    </div>
                    <div class="summary-item">
                        <strong>Personen:</strong> <span id="summary-persons">-</span>
                    </div>
                    <div class="summary-item">
                        <strong>Mitglied:</strong> <span id="summary-member">Nein</span>
                    </div>
                    <div class="summary-item" id="summary-options-wrapper" style="display: none;">
                        <strong>Extras:</strong>
                        <ul id="summary-options"></ul>
                    </div>
                </div>

                <?php if (!empty($room['show_planning_template'])): ?>
                <div class="form-group">
                    <label for="notes">Veranstaltungsplanung</label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="12" 
                              placeholder="<?= h($room['planning_template'] ?? 'Um Ihre Veranstaltung besser planen zu können, füllen Sie bitte folgende Punkte aus:\n\nVeranstaltungstitel:\n\nProgrammablauf:\n  Aufbau:\n  Einlass:\n  Beginn:\n  Ende:\n  Abbau:\n\nBesondere Wünsche oder Anmerkungen:\n\nÖffentliche Veranstaltung: Ja/Nein\nWenn Ja, soll die Veranstaltung auf unsere Website: Schicken Sie uns gerne einen Text und Bild per E-Mail.') ?>"></textarea>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label for="notes">Besondere Wünsche oder Anmerkungen</label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="4" 
                              placeholder="z.B. Bestuhlung, technische Anforderungen, etc."></textarea>
                </div>
                <?php endif; ?>

            <!-- Preisübersicht -->
            <div class="price-summary-box">
                <h3>Preisübersicht</h3>
                
                <?php if ($room['name'] === 'CLUB27'): ?>
                    <div class="price-info-box">
                        <strong>Preise CLUB27:</strong><br>
                        <span class="info-text">
                            <?php if ($isMember): ?>
                                ✅ Als Mitglied erhalten Sie 250€ Rabatt!<br>
                                💶 Raummiete: 750€ - Rabatt: 250€ = 500€<br>
                                💡 Bitte wählen Sie die Servicepauschale (250€) bei den Extras aus.<br>
                                📊 Gesamt: 750€ (inkl. Service)
                            <?php else: ?>
                                💶 Raummiete: 750€<br>
                                💡 Servicepauschale (250€) nicht vergessen!<br>
                                📊 Gesamt: 1000€ (inkl. Service)<br>
                                ✨ Mitglied werden und 250€ sparen!
                            <?php endif; ?>
                        </span>
                    </div>
                <?php elseif ($room['name'] === 'Tagungsraum' || $room['name'] === 'Club-Lounge'): ?>
                    <div class="price-info-box">
                        <strong>Preise <?= h($room['name']) ?>:</strong><br>
                        <span class="info-text">
                            <?php if ($isMember): ?>
                                ✅ Als Mitglied ist die Nutzung kostenfrei!<br>
                                💧 Kaltgetränke per Selbstabrechnung verfügbar.
                            <?php else: ?>
                                💶 100€ für die erste Stunde, jede weitere Stunde 50€<br>
                                💧 Kaltgetränke per Selbstabrechnung verfügbar.<br>
                                💡 Mitglied werden und kostenfrei nutzen!
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Raummiete:</span>
                        <span id="room-price-display"><?= number_format($roomPrice, 2, ',', '.') ?> €</span>
                    </div>
                    <div class="price-row" id="member-discount-row" style="display: none;">
                        <span>Mitglieder-Rabatt:</span>
                        <span id="member-discount-display" style="color: #28a745;">-250,00 €</span>
                    </div>
                    <div class="price-row">
                        <span>Extras:</span>
                        <span id="options-price-display">0,00 €</span>
                    </div>
                    <div class="price-row total">
                        <span><strong>Gesamtpreis:</strong></span>
                        <span id="total-price-display"><strong><?= number_format($roomPrice, 2, ',', '.') ?> €</strong></span>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submit-button">
                    <span class="button-text">✓ Buchungsanfrage senden</span>
                    <span class="button-loader" style="display: none;">
                        <span class="spinner"></span> Wird gesendet...
                    </span>
                </button>
            </div>

            <p class="form-hint">
                Nach dem Absenden erhalten Sie eine Bestätigungsmail. 
                Ein Administrator wird Ihre Anfrage prüfen und die Buchung bestätigen.
            </p>
            
            <div class="step-navigation">
                <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep(5)">← Zurück</button>
            </div>
            </section>
        </form>
    </div>

    <script src="assets/js/booking.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 5;
        
        // Step Navigation (global functions)
        window.nextStep = function(step) {
            // Validierung für aktuellen Schritt
            if (!validateStep(step)) {
                return;
            }
            
            // Update summary
            if (step === 4) {
                updateSummary();
            }
            
            // Hide current step
            document.querySelector(`.step-content[data-step="${step}"]`).style.display = 'none';
            document.querySelector(`.progress-step[data-step="${step}"]`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${step}"]`).classList.add('completed');
            
            // Show next step
            currentStep = step + 1;
            document.querySelector(`.step-content[data-step="${currentStep}"]`).style.display = 'block';
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
        
        window.prevStep = function(step) {
            // Hide current step
            document.querySelector(`.step-content[data-step="${step}"]`).style.display = 'none';
            document.querySelector(`.progress-step[data-step="${step}"]`).classList.remove('active');
            
            // Show previous step
            currentStep = step - 1;
            document.querySelector(`.step-content[data-step="${currentStep}"]`).style.display = 'block';
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('completed');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
        
        function validateStep(step) {
            switch(step) {
                case 1:
                    const date = document.getElementById('booking_date').value;
                    if (!date) {
                        alert('Bitte wählen Sie ein Datum aus.');
                        return false;
                    }
                    return true;
                    
                case 2:
                    const startTime = document.getElementById('start_time').value;
                    const endTime = document.getElementById('end_time').value;
                    if (!startTime || !endTime) {
                        alert('Bitte wählen Sie einen Zeitraum aus.');
                        return false;
                    }
                    
                    // Mindeststunden validieren
                    if (window.bookingCalendar && window.bookingCalendar.minHours > 0) {
                        const start = new Date('2000-01-01 ' + startTime);
                        const end = new Date('2000-01-01 ' + endTime);
                        const diffMs = end - start;
                        const diffHours = diffMs / (1000 * 60 * 60);
                        
                        if (diffHours < window.bookingCalendar.minHours) {
                            alert(`Die Mindestbuchungsdauer beträgt ${window.bookingCalendar.minHours} Stunde(n).\nIhr gewählter Zeitraum: ${diffHours.toFixed(2)} Stunde(n)`);
                            return false;
                        }
                    }
                    
                    return true;
                    
                case 3:
                    const name = document.getElementById('customer_name').value;
                    const email = document.getElementById('customer_email').value;
                    const numPersons = document.getElementById('num_persons').value;
                    if (!name || !email) {
                        alert('Bitte füllen Sie alle Pflichtfelder aus.');
                        return false;
                    }
                    if (!numPersons || numPersons < 1) {
                        alert('Bitte geben Sie die Anzahl der Personen an.');
                        return false;
                    }
                    return true;
                    
                case 4:
                    // Optional, keine Validierung nötig
                    return true;
                    
                default:
                    return true;
            }
        }
        
        function updateSummary() {
            // Datum
            const date = document.getElementById('booking_date').value;
            if (date) {
                const dateObj = new Date(date + 'T00:00:00');
                document.getElementById('summary-date').textContent = dateObj.toLocaleDateString('de-DE', { 
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
                });
            }
            
            // Zeit
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            if (startTime && endTime) {
                document.getElementById('summary-time').textContent = `${startTime} - ${endTime} Uhr`;
                
                // Pufferzeit berechnen und anzeigen
                const bufferBefore = <?= $room['buffer_before'] ?? 15 ?>;
                const bufferAfter = <?= $room['buffer_after'] ?? 15 ?>;
                
                if (bufferBefore > 0 || bufferAfter > 0) {
                    // Zeit-Objekte erstellen
                    const [startHour, startMin] = startTime.split(':').map(Number);
                    const [endHour, endMin] = endTime.split(':').map(Number);
                    
                    // Puffer hinzufügen
                    const bufferStart = new Date(2000, 0, 1, startHour, startMin - bufferBefore);
                    const bufferEnd = new Date(2000, 0, 1, endHour, endMin + bufferAfter);
                    
                    const bufferStartStr = bufferStart.toTimeString().substr(0, 5);
                    const bufferEndStr = bufferEnd.toTimeString().substr(0, 5);
                    
                    document.getElementById('summary-buffer').textContent = 
                        `${bufferBefore} Min. vorher + ${bufferAfter} Min. nachher`;
                    document.getElementById('summary-buffer-time').textContent = 
                        `${bufferStartStr} - ${bufferEndStr} Uhr`;
                    document.getElementById('summary-buffer-wrapper').style.display = 'block';
                } else {
                    document.getElementById('summary-buffer-wrapper').style.display = 'none';
                }
            }
            
            // Persönliche Daten
            document.getElementById('summary-name').textContent = document.getElementById('customer_name').value || '-';
            document.getElementById('summary-email').textContent = document.getElementById('customer_email').value || '-';
            document.getElementById('summary-phone').textContent = document.getElementById('customer_phone').value || '-';
            document.getElementById('summary-persons').textContent = document.getElementById('num_persons').value || '-';
            document.getElementById('summary-member').textContent = document.getElementById('is_member').checked ? 'Ja' : 'Nein';
            
            // Extras
            const selectedOptions = [];
            document.querySelectorAll('.option-checkbox:checked').forEach(cb => {
                const label = cb.nextElementSibling;
                const optionName = label.querySelector('strong').textContent;
                const quantityInput = document.getElementById('quantity_' + cb.value);
                const quantity = quantityInput ? quantityInput.value : 1;
                
                if (quantityInput && quantityInput.closest('.option-quantity').style.display !== 'none') {
                    selectedOptions.push(`${optionName} (${quantity}x)`);
                } else {
                    selectedOptions.push(optionName);
                }
            });
            
            if (selectedOptions.length > 0) {
                document.getElementById('summary-options-wrapper').style.display = 'block';
                document.getElementById('summary-options').innerHTML = selectedOptions.map(opt => `<li>${opt}</li>`).join('');
            } else {
                document.getElementById('summary-options-wrapper').style.display = 'none';
            }
        }
        
        // Kalender initialisieren (global verfügbar machen für Validierung)
        window.bookingCalendar = new BookingCalendar(<?= $room['id'] ?>, 'calendar-container');
        
        // Preisrechner initialisieren
        const roomData = {
            id: <?= $room['id'] ?>,
            name: '<?= addslashes($room['name']) ?>',
            priceMember: <?= $room['price_member'] ?>,
            priceNonMember: <?= $room['price_non_member'] ?>
        };
        
        const priceCalculator = new PriceCalculator(<?= $roomPrice ?>, roomData);
        
        // Preisberechnung aktualisieren bei Zeitänderung (für stundenweise Abrechnung)
        document.getElementById('start_time').addEventListener('change', updateRoomPrice);
        document.getElementById('end_time').addEventListener('change', updateRoomPrice);
        
        function updateRoomPrice() {
            const isMember = document.getElementById('is_member').checked;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            let newPrice = 0;
            let discount = 0;
            
            if (roomData.name === 'CLUB27') {
                // CLUB27: Grundpreis 750€, Mitglieder erhalten 250€ Rabatt
                newPrice = 750;
                if (isMember) {
                    discount = 250;
                }
            } else if (roomData.name === 'Tagungsraum' || roomData.name === 'Club-Lounge') {
                if (!isMember && startTime && endTime) {
                    // Stundenweise berechnen
                    const start = new Date('2000-01-01 ' + startTime);
                    const end = new Date('2000-01-01 ' + endTime);
                    const hours = Math.ceil((end - start) / (1000 * 60 * 60));
                    
                    // Erste Stunde 100€, jede weitere 50€
                    newPrice = 100 + (Math.max(0, hours - 1) * 50);
                } else {
                    newPrice = 0;
                }
            } else {
                newPrice = isMember ? roomData.priceMember : roomData.priceNonMember;
            }
            
            priceCalculator.roomPrice = newPrice;
            priceCalculator.memberDiscount = discount;
            priceCalculator.updateOptions();
        }
        
        // Event Listeners für Mitgliedschaft-Änderung
        document.getElementById('is_member').addEventListener('change', updateRoomPrice);
        
        // Initiale Preisberechnung beim Laden der Seite
        updateRoomPrice();
        
        // Event Listeners für Optionen
        document.querySelectorAll('.option-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allowQuantity = this.getAttribute('data-allow-quantity') === '1';
                const quantityWrapper = document.getElementById('quantity_wrapper_' + this.value);
                if (quantityWrapper && allowQuantity) {
                    quantityWrapper.style.display = this.checked ? 'block' : 'none';
                    // Setze Mindestanzahl beim Anzeigen
                    const minQuantity = parseInt(this.getAttribute('data-min-quantity')) || 1;
                    const quantityInput = document.getElementById('quantity_' + this.value);
                    if (quantityInput) {
                        if (this.checked) {
                            quantityInput.value = minQuantity;
                            quantityInput.disabled = false;
                        } else {
                            quantityInput.disabled = true;
                        }
                    }
                }
                priceCalculator.updateOptions();
            });
        });
        
        // Event Listeners für Mengenänderungen
        document.querySelectorAll('.option-quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                priceCalculator.updateOptions();
            });
        });
        
        // Event Listeners für "Alle"-Buttons
        document.querySelectorAll('.set-all-persons-btn').forEach(button => {
            button.addEventListener('click', function() {
                const optionId = this.getAttribute('data-option-id');
                const minQuantity = parseInt(this.getAttribute('data-min-quantity')) || 1;
                const personsInput = document.getElementById('num_persons');
                const quantityInput = document.getElementById('quantity_' + optionId);
                
                if (personsInput && quantityInput) {
                    const persons = parseInt(personsInput.value) || 1;
                    // Verwende die höhere Zahl zwischen Personenanzahl und Mindestanzahl
                    quantityInput.value = Math.max(persons, minQuantity);
                    priceCalculator.updateOptions();
                }
            });
        });
        
        // Formular-Validierung
        document.getElementById('booking-form').addEventListener('submit', function(e) {
            const bookingDate = document.getElementById('booking_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (!bookingDate) {
                e.preventDefault();
                alert('Bitte wählen Sie ein Datum aus.');
                return false;
            }
            
            if (!startTime || !endTime) {
                e.preventDefault();
                alert('Bitte wählen Sie einen Zeitraum aus.');
                return false;
            }
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('Die Endzeit muss nach der Startzeit liegen.');
                return false;
            }
            
            // Loading-State aktivieren
            const submitButton = document.getElementById('submit-button');
            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
            
            submitButton.disabled = true;
            buttonText.style.display = 'none';
            buttonLoader.style.display = 'inline-flex';
            
            return true;
        });
    </script>
<script>
  // optional: ID aus der URL lesen, damit der Parent weiß, welches iframe gemeint ist
  function getParam(name) {
    const m = new RegExp('[?&]' + name + '=([^&#]*)').exec(location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : null;
  }
  const frameId = getParam('frameId') || null;

  // Höhe zuverlässig bestimmen
  function measureHeight() {
    const { body, documentElement: html } = document;
    return Math.max(
      body.scrollHeight, html.scrollHeight,
      body.offsetHeight, html.offsetHeight,
      body.clientHeight, html.clientHeight
    );
  }

  // Throttling, damit bei vielen Änderungen nicht gespammt wird
  let ticking = false;
  function postHeight() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const height = measureHeight();
      // type & optional id mitgeben; targetOrigin wird im Parent gefiltert
      window.parent.postMessage({ type: 'IFRAME_HEIGHT', height, id: frameId }, '*');
      ticking = false;
    });
  }

  // Initial & onload
  window.addEventListener('load', postHeight);

  // ResizeObserver für Layout-/Content-Änderungen
  if ('ResizeObserver' in window) {
    const ro = new ResizeObserver(postHeight);
    ro.observe(document.documentElement);
    ro.observe(document.body);
  }

  // Fallback: MutationObserver für DOM-Änderungen
  const mo = new MutationObserver(postHeight);
  mo.observe(document.documentElement, { childList: true, subtree: true, attributes: true, characterData: true });

  // Falls Schriften/CSS nachladen und Layout verändern
  window.addEventListener('resize', postHeight);
  document.fonts && document.fonts.addEventListener && document.fonts.addEventListener('loadingdone', postHeight);

  // Optional: periodischer Safeguard (z. B. alle 1.5s)
  setInterval(postHeight, 1500);
</script>

</body>
</html>
