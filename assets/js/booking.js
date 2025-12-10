/**
 * Buchungskalender JavaScript
 */

class BookingCalendar {
    constructor(roomId, containerId) {
        this.roomId = roomId;
        this.container = document.getElementById(containerId);
        this.selectedDate = null;
        this.selectedStartTime = null;
        this.selectedEndTime = null;
        this.bookedSlots = [];
        this.minHours = 1.0;
        this.monthEvents = {}; // Speichert Events pro Monat
        
        this.init();
    }
    
    init() {
        this.renderCalendar();
        this.attachEventListeners();
    }
    
    renderCalendar() {
        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();
        
        this.displayMonth(currentYear, currentMonth);
    }
    
    async loadMonthEvents(year, month) {
        const monthKey = `${year}-${String(month + 1).padStart(2, '0')}`;
        
        // Bereits geladen? Dann überspringen
        if (this.monthEvents[monthKey]) {
            return;
        }
        
        try {
            const response = await fetch(`api/calendar-month.php?room_id=${this.roomId}&year=${year}&month=${month + 1}`);
            const data = await response.json();
            
            if (data.success) {
                this.monthEvents[monthKey] = {
                    google: data.google_events || {},
                    local: data.local_bookings || {}
                };
            }
        } catch (error) {
            console.error('Fehler beim Laden der Monats-Events:', error);
            this.monthEvents[monthKey] = { google: {}, local: {} };
        }
    }
    
    async displayMonth(year, month) {
        // Monat normalisieren (falls außerhalb 0-11)
        const date = new Date(year, month, 1);
        const normalizedYear = date.getFullYear();
        const normalizedMonth = date.getMonth();
        
        // Events für diesen Monat laden
        await this.loadMonthEvents(normalizedYear, normalizedMonth);
        
        const firstDay = new Date(normalizedYear, normalizedMonth, 1);
        const lastDay = new Date(normalizedYear, normalizedMonth + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();
        
        const monthNames = [
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        ];
        
        // Vorheriger und nächster Monat berechnen
        const prevDate = new Date(normalizedYear, normalizedMonth - 1, 1);
        const nextDate = new Date(normalizedYear, normalizedMonth + 1, 1);
        
        let html = `
            <div class="calendar-header">
                <button type="button" class="btn-prev-month" data-year="${prevDate.getFullYear()}" data-month="${prevDate.getMonth()}">
                    &laquo;
                </button>
                <h3>${monthNames[normalizedMonth]} ${normalizedYear}</h3>
                <button type="button" class="btn-next-month" data-year="${nextDate.getFullYear()}" data-month="${nextDate.getMonth()}">
                    &raquo;
                </button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-weekdays">
                    <div>Mo</div>
                    <div>Di</div>
                    <div>Mi</div>
                    <div>Do</div>
                    <div>Fr</div>
                    <div>Sa</div>
                    <div>So</div>
                </div>
                <div class="calendar-days">
        `;
        
        // Leere Zellen für Tage vor dem 1. des Monats
        const startDay = startingDayOfWeek === 0 ? 6 : startingDayOfWeek - 1;
        for (let i = 0; i < startDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Tage des Monats
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let day = 1; day <= daysInMonth; day++) {
            const currentDate = new Date(normalizedYear, normalizedMonth, day);
            const dateString = this.formatDate(currentDate);
            const isPast = currentDate < today;
            const isToday = currentDate.getTime() === today.getTime();
            
            // Prüfe ob es Events an diesem Tag gibt
            const monthKey = `${normalizedYear}-${String(normalizedMonth + 1).padStart(2, '0')}`;
            const hasEvents = this.monthEvents[monthKey] && 
                             (this.monthEvents[monthKey].google[dateString] || 
                              this.monthEvents[monthKey].local[dateString]);
            
            let classes = 'calendar-day';
            if (isPast) classes += ' past';
            if (isToday) classes += ' today';
            if (this.selectedDate === dateString) classes += ' selected';
            if (hasEvents) classes += ' has-events';
            
            html += `
                <div class="${classes}" data-date="${dateString}" ${isPast ? 'data-disabled="true"' : ''}>
                    <span>${day}</span>
                    ${hasEvents ? '<div class="event-indicator"></div>' : ''}
                </div>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
        
        this.container.innerHTML = html;
        this.attachEventListeners();
    }
    
    attachEventListeners() {
        // Kalender-Tage
        this.container.querySelectorAll('.calendar-day:not(.empty):not(.past)').forEach(day => {
            day.addEventListener('click', (e) => {
                const date = e.currentTarget.getAttribute('data-date');
                this.selectDate(date);
            });
        });
        
        // Monat navigation
        const prevBtn = this.container.querySelector('.btn-prev-month');
        const nextBtn = this.container.querySelector('.btn-next-month');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                const year = parseInt(e.currentTarget.getAttribute('data-year'));
                const month = parseInt(e.currentTarget.getAttribute('data-month'));
                this.displayMonth(year, month);
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                const year = parseInt(e.currentTarget.getAttribute('data-year'));
                const month = parseInt(e.currentTarget.getAttribute('data-month'));
                this.displayMonth(year, month);
            });
        }
    }
    
    async selectDate(date) {
        this.selectedDate = date;
        
        // Visual feedback
        this.container.querySelectorAll('.calendar-day').forEach(day => {
            day.classList.remove('selected');
        });
        this.container.querySelector(`[data-date="${date}"]`)?.classList.add('selected');
        
        // Datum ins Formular eintragen
        document.getElementById('booking_date').value = date;
        
        // Verfügbarkeit laden
        await this.loadAvailability(date);
    }
    
    async loadAvailability(date) {
        const timeSlotsContainer = document.getElementById('time-slots');
        if (!timeSlotsContainer) return;
        
        timeSlotsContainer.innerHTML = '<div class="loading">Verfügbarkeit wird geladen...</div>';
        
        try {
            const response = await fetch(`api/availability.php?room_id=${this.roomId}&date=${date}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Fehler beim Laden der Verfügbarkeit');
            }
            
            // Gebuchte Zeitslots aus Google Calendar und lokaler DB
            this.bookedSlots = [
                ...(data.google_slots || []),
                ...(data.local_slots || [])
            ];
            
            // Mindeststunden speichern
            this.minHours = data.min_hours || 1.0;
            
            this.displayTimeSlots();
            
            // Formular-Abschnitt anzeigen
            document.getElementById('booking-form-section')?.classList.remove('hidden');
            
        } catch (error) {
            console.error('Fehler:', error);
            timeSlotsContainer.innerHTML = `<div class="error">Fehler beim Laden: ${error.message}</div>`;
        }
    }
    
    displayTimeSlots() {
        const timeSlotsContainer = document.getElementById('time-slots');
        if (!timeSlotsContainer) return;
        
        // Alle 15-Minuten-Slots von 08:00 bis 24:00 generieren
        const allSlots = this.generateAllTimeSlots();
        
        let html = '<div class="time-range-info"><strong>Wählen Sie einen Zeitraum:</strong> Klicken Sie auf einen Startzeitpunkt, dann auf einen Endzeitpunkt.</div>';
        
        // Hinweis zu Mindeststunden anzeigen
        if (this.minHours > 1) {
            html += `<div class="min-hours-info" style="background: #fff5f0; padding: 12px; border-left: 4px solid #E35205; margin: 10px 0; border-radius: 4px;">
                <strong>⏱️ Mindestbuchungsdauer:</strong> ${this.minHours} Stunde(n)
            </div>`;
        }
        
        html += '<div class="time-slots-grid-range">';
        
        allSlots.forEach((time) => {
            const bookedInfo = this.getBookedSlotInfo(time);
            const isBooked = bookedInfo !== null;
            const isStart = this.selectedStartTime === time;
            const isEnd = this.selectedEndTime === time;
            const isInRange = this.isTimeInRange(time);
            
            let classes = 'time-slot-range';
            if (isBooked) classes += ' booked';
            if (isStart) classes += ' start';
            if (isEnd) classes += ' end';
            if (isInRange) classes += ' in-range';
            
            let title = time;
            if (isBooked && bookedInfo) {
                title = `Gebucht: ${bookedInfo}`;
            }
            
            html += `
                <div class="${classes}" 
                     data-time="${time}"
                     title="${title}"
                     ${isBooked ? 'data-booked="true"' : ''}>
                    ${time}
                </div>
            `;
        });
        
        html += '</div>';
        html += '<p class="hint">🟢 Verfügbar | 🔴 Bereits gebucht (inkl. Puffer)</p>';
        
        timeSlotsContainer.innerHTML = html;
        
        // Event Listeners für Zeitslots
        timeSlotsContainer.querySelectorAll('.time-slot-range:not([data-booked])').forEach(slot => {
            slot.addEventListener('click', (e) => {
                const time = e.currentTarget.getAttribute('data-time');
                this.handleTimeRangeSelection(time);
            });
        });
    }
    
    generateAllTimeSlots() {
        const slots = [];
        for (let hour = 8; hour <= 23; hour++) {
            for (let minute = 0; minute < 60; minute += 15) {
                const timeString = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                slots.push(timeString);
            }
        }
        // Letzter Slot: 23:59 statt 24:00 (HTML time input unterstützt nur 00-23)
        slots.push('23:59');
        return slots;
    }
    
    isTimeSlotBooked(time) {
        return this.getBookedSlotInfo(time) !== null;
    }
    
    getBookedSlotInfo(time) {
        if (!this.bookedSlots) return null;
        
        const timeMinutes = this.timeToMinutes(time);
        
        for (const booking of this.bookedSlots) {
            const startMinutes = this.timeToMinutes(booking.start);
            const endMinutes = this.timeToMinutes(booking.end);
            
            // Prüfe ob Zeit in gebuchtem Zeitraum liegt (inkl. Puffer)
            // Bei ganztägigen Events (23:59) muss <= verwendet werden, sonst wird der letzte Slot nicht blockiert
            if (timeMinutes >= startMinutes && timeMinutes <= endMinutes) {
                if (booking.actual_start && booking.actual_end) {
                    return `${booking.actual_start}-${booking.actual_end} (Puffer: ${booking.start}-${booking.end})`;
                }
                return `${booking.start}-${booking.end}`;
            }
        }
        
        return null;
    }
    
    isTimeInRange(time) {
        if (!this.selectedStartTime || !this.selectedEndTime) return false;
        
        const timeMinutes = this.timeToMinutes(time);
        const startMinutes = this.timeToMinutes(this.selectedStartTime);
        const endMinutes = this.timeToMinutes(this.selectedEndTime);
        
        return timeMinutes > startMinutes && timeMinutes < endMinutes;
    }
    
    timeToMinutes(timeString) {
        const [hours, minutes] = timeString.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    handleTimeRangeSelection(time) {
        if (!this.selectedStartTime) {
            // Ersten Zeitpunkt auswählen (Start)
            this.selectedStartTime = time;
            this.selectedEndTime = null;
        } else if (!this.selectedEndTime) {
            // Zweiten Zeitpunkt auswählen (Ende)
            const startMinutes = this.timeToMinutes(this.selectedStartTime);
            const endMinutes = this.timeToMinutes(time);
            
            if (endMinutes <= startMinutes) {
                // Zurückliegender Zeitpunkt: Reset und neuer Start
                this.selectedStartTime = time;
                this.selectedEndTime = null;
            } else {
                // Prüfe ob Zeitraum verfügbar ist (keine gebuchten Slots dazwischen)
                if (this.isRangeAvailable(this.selectedStartTime, time)) {
                    this.selectedEndTime = time;
                    
                    // Formular aktualisieren
                    document.getElementById('booking_date').value = this.selectedDate;
                    document.getElementById('start_time').value = this.selectedStartTime;
                    document.getElementById('end_time').value = this.selectedEndTime;
                } else {
                    alert('Der gewählte Zeitraum enthält bereits gebuchte Zeiten. Bitte wählen Sie einen anderen Zeitraum.');
                    this.selectedStartTime = null;
                    this.selectedEndTime = null;
                }
            }
        } else {
            // Neuauswahl: Reset und neuen Start setzen
            this.selectedStartTime = time;
            this.selectedEndTime = null;
        }
        
        this.displayTimeSlots();
    }
    
    isRangeAvailable(startTime, endTime) {
        if (!this.bookedSlots) return true;
        
        const startMinutes = this.timeToMinutes(startTime);
        const endMinutes = this.timeToMinutes(endTime);
        
        return !this.bookedSlots.some(booking => {
            const bookingStart = this.timeToMinutes(booking.start);
            const bookingEnd = this.timeToMinutes(booking.end);
            
            // Prüfe Überschneidungen
            return (startMinutes < bookingEnd && endMinutes > bookingStart);
        });
    }
    

    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    getSelectedDate() {
        return this.selectedDate;
    }
    
    getSelectedTimeSlot() {
        if (!this.selectedStartTime || !this.selectedEndTime) return null;
        return {
            start: this.selectedStartTime,
            end: this.selectedEndTime
        };
    }
}

// Preisberechnung
class PriceCalculator {
    constructor(roomPrice, roomData = null) {
        this.roomPrice = parseFloat(roomPrice);
        this.roomData = roomData;
        this.optionsPrice = 0;
        this.totalPrice = this.roomPrice;
    }
    
    updateOptions() {
        this.optionsPrice = 0;
        
        document.querySelectorAll('.option-checkbox:checked').forEach(checkbox => {
            const price = parseFloat(checkbox.getAttribute('data-price'));
            const quantityInput = document.getElementById('quantity_' + checkbox.value);
            const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
            
            this.optionsPrice += price * quantity;
        });
        
        this.totalPrice = this.roomPrice + this.optionsPrice;
        this.updateDisplay();
    }
    
    updateDisplay() {
        document.getElementById('room-price-display').textContent = 
            this.formatPrice(this.roomPrice);
        document.getElementById('options-price-display').textContent = 
            this.formatPrice(this.optionsPrice);
        document.getElementById('total-price-display').textContent = 
            this.formatPrice(this.totalPrice);
            
        document.getElementById('room_price').value = this.roomPrice.toFixed(2);
        document.getElementById('options_price').value = this.optionsPrice.toFixed(2);
        document.getElementById('total_price').value = this.totalPrice.toFixed(2);
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }
}
