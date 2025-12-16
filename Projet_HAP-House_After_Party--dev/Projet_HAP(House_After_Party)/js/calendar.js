/**
 * Calendrier des disponibilités
 * Composant JS pour afficher les disponibilités d'un bien
 */

class AvailabilityCalendar {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            bienId: options.bienId || null,
            apiUrl: options.apiUrl || '../api/get_availability.php',
            selectable: options.selectable || false,
            onDateSelect: options.onDateSelect || null,
            onRangeSelect: options.onRangeSelect || null,
            minDate: options.minDate || new Date(),
            showPrices: options.showPrices || false,
            locale: options.locale || 'fr-FR'
        };
        
        this.currentDate = new Date();
        this.selectedStartDate = null;
        this.selectedEndDate = null;
        this.unavailableDates = [];
        this.pendingDates = [];
        this.prices = {};
        
        this.monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                           'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        this.dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        
        this.init();
    }
    
    init() {
        this.render();
        this.fetchAvailability();
        this.attachEvents();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <button class="calendar-nav prev" data-action="prev">‹</button>
                    <h3 class="calendar-title"></h3>
                    <button class="calendar-nav next" data-action="next">›</button>
                </div>
                <div class="calendar-days-header"></div>
                <div class="calendar-grid"></div>
                <div class="calendar-legend">
                    <div class="legend-item"><span class="dot available"></span> Disponible</div>
                    <div class="legend-item"><span class="dot unavailable"></span> Réservé</div>
                    <div class="legend-item"><span class="dot pending"></span> En attente</div>
                    ${this.options.selectable ? '<div class="legend-item"><span class="dot selected"></span> Sélectionné</div>' : ''}
                </div>
            </div>
        `;
        
        this.updateCalendarView();
    }
    
    updateCalendarView() {
        const title = this.container.querySelector('.calendar-title');
        title.textContent = `${this.monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
        
        // Jours de la semaine
        const daysHeader = this.container.querySelector('.calendar-days-header');
        daysHeader.innerHTML = this.dayNames.map(day => `<div class="day-name">${day}</div>`).join('');
        
        // Grille du calendrier
        const grid = this.container.querySelector('.calendar-grid');
        grid.innerHTML = this.generateDaysGrid();
    }
    
    generateDaysGrid() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // Ajuster pour commencer le lundi (0 = dimanche -> 6, 1 = lundi -> 0, etc.)
        let startOffset = firstDay.getDay() - 1;
        if (startOffset < 0) startOffset = 6;
        
        let html = '';
        
        // Jours vides avant le 1er
        for (let i = 0; i < startOffset; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Jours du mois
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const date = new Date(year, month, day);
            
            let classes = ['calendar-day'];
            let priceHtml = '';
            
            // Vérifier si passé
            if (date < today) {
                classes.push('past');
            }
            // Vérifier si aujourd'hui
            if (date.toDateString() === today.toDateString()) {
                classes.push('today');
            }
            // Vérifier si indisponible
            if (this.unavailableDates.includes(dateStr)) {
                classes.push('unavailable');
            }
            // Vérifier si en attente
            else if (this.pendingDates.includes(dateStr)) {
                classes.push('pending');
            }
            // Vérifier si sélectionné
            if (this.isDateSelected(date)) {
                classes.push('selected');
            }
            // Dans la plage sélectionnée
            if (this.isDateInRange(date)) {
                classes.push('in-range');
            }
            
            // Prix
            if (this.options.showPrices && this.prices[dateStr]) {
                priceHtml = `<span class="day-price">${this.prices[dateStr]}€</span>`;
            }
            
            html += `
                <div class="${classes.join(' ')}" data-date="${dateStr}">
                    <span class="day-number">${day}</span>
                    ${priceHtml}
                </div>
            `;
        }
        
        return html;
    }
    
    isDateSelected(date) {
        if (!this.selectedStartDate) return false;
        
        const dateStr = date.toDateString();
        if (this.selectedStartDate.toDateString() === dateStr) return true;
        if (this.selectedEndDate && this.selectedEndDate.toDateString() === dateStr) return true;
        
        return false;
    }
    
    isDateInRange(date) {
        if (!this.selectedStartDate || !this.selectedEndDate) return false;
        return date > this.selectedStartDate && date < this.selectedEndDate;
    }
    
    fetchAvailability() {
        if (!this.options.bienId) return;
        
        const month = this.currentDate.getMonth() + 1;
        const year = this.currentDate.getFullYear();
        
        fetch(`${this.options.apiUrl}?bien_id=${this.options.bienId}&month=${month}&year=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.unavailableDates = data.unavailable || [];
                    this.pendingDates = data.pending || [];
                    this.prices = data.prices || {};
                    this.updateCalendarView();
                }
            })
            .catch(error => console.error('Erreur chargement disponibilités:', error));
    }
    
    attachEvents() {
        // Navigation
        this.container.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="prev"]')) {
                this.previousMonth();
            } else if (e.target.matches('[data-action="next"]')) {
                this.nextMonth();
            } else if (e.target.matches('.calendar-day:not(.empty):not(.past):not(.unavailable)')) {
                this.handleDateClick(e.target);
            } else if (e.target.closest('.calendar-day:not(.empty):not(.past):not(.unavailable)')) {
                this.handleDateClick(e.target.closest('.calendar-day'));
            }
        });
    }
    
    handleDateClick(dayElement) {
        if (!this.options.selectable) return;
        
        const dateStr = dayElement.dataset.date;
        const date = new Date(dateStr + 'T00:00:00');
        
        // Vérifier si date disponible
        if (this.unavailableDates.includes(dateStr)) return;
        
        // Logique de sélection de plage
        if (!this.selectedStartDate || (this.selectedStartDate && this.selectedEndDate)) {
            // Première sélection ou reset
            this.selectedStartDate = date;
            this.selectedEndDate = null;
        } else {
            // Deuxième sélection
            if (date < this.selectedStartDate) {
                this.selectedEndDate = this.selectedStartDate;
                this.selectedStartDate = date;
            } else {
                this.selectedEndDate = date;
            }
            
            // Vérifier si des dates indisponibles sont dans la plage
            if (this.hasUnavailableInRange()) {
                alert('Certaines dates de cette période sont déjà réservées.');
                this.selectedStartDate = date;
                this.selectedEndDate = null;
            }
        }
        
        this.updateCalendarView();
        
        // Callbacks
        if (this.options.onDateSelect) {
            this.options.onDateSelect(this.selectedStartDate, this.selectedEndDate);
        }
        
        if (this.selectedStartDate && this.selectedEndDate && this.options.onRangeSelect) {
            this.options.onRangeSelect(this.selectedStartDate, this.selectedEndDate);
        }
    }
    
    hasUnavailableInRange() {
        if (!this.selectedStartDate || !this.selectedEndDate) return false;
        
        const start = new Date(this.selectedStartDate);
        const end = new Date(this.selectedEndDate);
        
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
            const dateStr = d.toISOString().split('T')[0];
            if (this.unavailableDates.includes(dateStr)) {
                return true;
            }
        }
        
        return false;
    }
    
    previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.fetchAvailability();
    }
    
    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.fetchAvailability();
    }
    
    goToMonth(month, year) {
        this.currentDate = new Date(year, month - 1, 1);
        this.fetchAvailability();
    }
    
    getSelectedRange() {
        return {
            start: this.selectedStartDate,
            end: this.selectedEndDate
        };
    }
    
    clearSelection() {
        this.selectedStartDate = null;
        this.selectedEndDate = null;
        this.updateCalendarView();
    }
}

// Exporter
window.AvailabilityCalendar = AvailabilityCalendar;
