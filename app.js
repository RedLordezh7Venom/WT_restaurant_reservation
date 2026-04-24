/**
 * Restaurant Reservation System — Main JS
 * Three-stage interactive floor plan: Floors → Rooms → Tables
 * Connects to api.php (PHP + MySQL backend)
 */

class RestaurantApp {
    constructor() {
        // Selection state
        this.currentUser    = null;
        this.selectedDate   = null;
        this.selectedTime   = null;
        this.selectedTable  = null;
        this.currentFloor   = null;
        this.currentRoom    = null;
        this.currentMonth   = new Date();

        // Stage: 'floors' | 'rooms' | 'tables'
        this.stage = 'floors';

        this.init();
    }

    /* ========================================================
       INIT
    ======================================================== */
    init() {
        this.setupEventListeners();
        this.renderCalendar();
        this.renderTimeSlots();
        this.loadFloors();
        this.checkSession();
        this.populateWaitlistTimes();

        // Real-time refresh every 30 seconds
        setInterval(() => this.refreshAvailability(), 30000);
    }

    /* ========================================================
       EVENT LISTENERS
    ======================================================== */
    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', e => this.navigate(e.target.closest('.nav-item')));
        });

        // Auth
        document.getElementById('login-btn').addEventListener('click', () => this.showLogin());
        document.getElementById('logout-btn').addEventListener('click', () => this.logout());
        document.getElementById('close-login').addEventListener('click', () => this.hideModal('login-modal'));
        document.getElementById('submit-login').addEventListener('click', () => this.handleLogin());
        document.getElementById('login-password').addEventListener('keypress', e => {
            if (e.key === 'Enter') this.handleLogin();
        });

        // Booking
        document.getElementById('confirm-booking-btn').addEventListener('click', () => this.confirmBooking());
        document.getElementById('party-size').addEventListener('change', () => this.refreshAvailability());

        // Confirmation modal
        document.getElementById('close-confirmation').addEventListener('click', () => this.hideModal('confirmation-modal'));
        document.getElementById('close-confirmation-btn').addEventListener('click', () => this.hideModal('confirmation-modal'));

        // Waitlist
        document.getElementById('join-waitlist-btn').addEventListener('click', () => this.joinWaitlist());

        // Admin tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', e => this.switchAdminTab(e.target));
        });

        // Modal backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) this.hideModal(modal.id);
            });
        });

        // Breadcrumb back navigation
        document.getElementById('bc-building').addEventListener('click', () => this.goToStage('floors'));
        document.getElementById('bc-floor').addEventListener('click', () => this.goToStage('rooms'));
    }

    /* ========================================================
       NAVIGATION
    ======================================================== */
    navigate(navItem) {
        const page = navItem.dataset.page;
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        navItem.classList.add('active');
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById(`page-${page}`).classList.add('active');

        if (page === 'my-reservations') this.loadMyReservations();
        else if (page === 'admin') this.loadAdminData();
        else if (page === 'waitlist') this.loadWaitlist();
    }

    /* ========================================================
       CALENDAR
    ======================================================== */
    renderCalendar() {
        const container = document.getElementById('calendar-container');
        const year  = this.currentMonth.getFullYear();
        const month = this.currentMonth.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay  = new Date(year, month + 1, 0);
        const today = new Date(); today.setHours(0, 0, 0, 0);

        let html = `
            <div class="calendar-header">
                <button class="calendar-nav" id="prev-month">◀</button>
                <div class="calendar-month">${firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</div>
                <button class="calendar-nav" id="next-month">▶</button>
            </div>
            <div class="calendar-grid">
                ${['S','M','T','W','T','F','S'].map(d => `<div class="calendar-day-header">${d}</div>`).join('')}
        `;

        const startDay = firstDay.getDay();
        const prevMonth = new Date(year, month, 0);
        for (let i = startDay - 1; i >= 0; i--) {
            html += `<div class="calendar-day other-month">${prevMonth.getDate() - i}</div>`;
        }
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const date = new Date(year, month, day);
            const isPast     = date < today;
            const isToday    = date.getTime() === today.getTime();
            const isSelected = this.selectedDate && date.getTime() === this.selectedDate.getTime();
            let classes = 'calendar-day';
            if (isPast)     classes += ' disabled';
            if (isToday)    classes += ' today';
            if (isSelected) classes += ' selected';
            html += `<div class="${classes}" data-date="${date.toISOString()}">${day}</div>`;
        }
        const remaining = 42 - (startDay + lastDay.getDate());
        for (let day = 1; day <= remaining; day++) {
            html += `<div class="calendar-day other-month">${day}</div>`;
        }
        html += '</div>';
        container.innerHTML = html;

        document.getElementById('prev-month').addEventListener('click', () => {
            this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
            this.renderCalendar();
        });
        document.getElementById('next-month').addEventListener('click', () => {
            this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
            this.renderCalendar();
        });
        document.querySelectorAll('.calendar-day:not(.disabled):not(.other-month)').forEach(day => {
            day.addEventListener('click', e => this.selectDate(e.target));
        });
    }

    selectDate(element) {
        this.selectedDate = new Date(element.dataset.date);
        document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
        element.classList.add('selected');
        this.updateTimeSlots();
        this.refreshAvailability();
        this.showNotification('Date Selected', this.selectedDate.toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        }), 'success');
    }

    /* ========================================================
       TIME SLOTS
    ======================================================== */
    renderTimeSlots() {
        const container = document.getElementById('time-slots');
        const slots = this.generateTimeSlots();
        container.innerHTML = slots.map(slot =>
            `<div class="time-slot" data-time="${slot}">${slot}</div>`
        ).join('');
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', e => this.selectTimeSlot(e.target));
        });
        // Also populate waitlist time dropdown
        this.populateWaitlistTimes(slots);
    }

    generateTimeSlots() {
        const slots = [];
        for (let hour = 11; hour <= 21; hour++) {
            for (const min of [0, 30]) {
                const d = new Date(2000, 0, 1, hour, min);
                slots.push(d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }));
            }
        }
        return slots;
    }

    populateWaitlistTimes(slots = null) {
        const sel = document.getElementById('waitlist-time');
        if (!sel) return;
        if (!slots) slots = this.generateTimeSlots();
        sel.innerHTML = '<option value="">Any time</option>' +
            slots.map(s => `<option value="${s}">${s}</option>`).join('');
    }

    selectTimeSlot(element) {
        if (element.classList.contains('disabled')) return;
        this.selectedTime = element.dataset.time;
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        element.classList.add('selected');
        this.refreshAvailability();
        this.showNotification('Time Selected', this.selectedTime, 'success');
    }

    async updateTimeSlots() {
        if (!this.selectedDate) return;
        const dateStr = this.selectedDate.toISOString().split('T')[0];
        try {
            const data = await this.api({ action: 'get_available_slots', date: dateStr });
            if (data.success) {
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.classList.toggle('disabled', !data.availableSlots.includes(slot.dataset.time));
                });
            }
        } catch (e) { /* ignore */ }
    }

    /* ========================================================
       FLOOR PLAN — STAGE MANAGER
    ======================================================== */

    // Switch between stages with animation
    goToStage(stage) {
        const stages = ['floors', 'rooms', 'tables'];
        stages.forEach(s => {
            const el = document.getElementById(`stage-${s}`);
            el.classList.remove('active-stage', 'stage-exit');
            el.style.display = 'none';
        });
        const target = document.getElementById(`stage-${stage}`);
        target.style.display = 'block';
        target.classList.add('active-stage');
        this.stage = stage;
        this.updateBreadcrumb();

        if (stage === 'floors') {
            this.currentFloor = null;
            this.currentRoom  = null;
            this.selectedTable = null;
            document.getElementById('selected-table-info').style.display = 'none';
            // Clear zoom/fade animation classes so building re-appears cleanly
            document.querySelectorAll('.building-floor').forEach(f => {
                f.classList.remove('floor-zooming', 'floor-fade-out');
            });
        }
    }

    updateBreadcrumb() {
        const bc      = document.getElementById('floor-breadcrumb');
        const bcFloor = document.getElementById('bc-floor');
        const bcFloorSep = document.getElementById('bc-floor-sep');
        const bcRoom  = document.getElementById('bc-room');

        if (this.stage === 'floors') {
            bc.style.display = 'none';
            return;
        }
        bc.style.display = 'flex';
        if (this.currentFloor) {
            bcFloor.textContent = this.currentFloor.name;
            bcFloor.style.display = 'inline-block';
            bcFloorSep.style.display = 'inline-block';
        } else {
            bcFloor.style.display = 'none';
            bcFloorSep.style.display = 'none';
        }
        if (this.stage === 'tables' && this.currentRoom) {
            bcRoom.textContent = this.currentRoom.name;
            bcRoom.style.display = 'inline-block';
        } else {
            bcRoom.style.display = 'none';
        }
    }

    /* ========================================================
       STAGE 1: FLOORS
    ======================================================== */
    async loadFloors() {
        try {
            const data = await this.api({ action: 'get_floors' });
            if (!data.success) return;
            const grid = document.getElementById('floors-grid');
            if (!data.floors.length) {
                grid.innerHTML = '<p class="empty-msg">No floors configured.</p>';
                return;
            }

            // Sort ASC by display_order, then reverse so top floor is first in DOM
            // (flex column renders top-to-bottom = Rooftop at top, Ground at bottom)
            const floors = [...data.floors].sort((a, b) => a.display_order - b.display_order).reverse();
            const n = floors.length;

            // Colour class based on vertical position: top→mid→ground
            const colourClasses = ['bfloor-top', 'bfloor-mid', 'bfloor-ground'];

            grid.className = 'building-stack';
            grid.innerHTML = floors.map((floor, idx) => {
                // Width: grows as we go down. top = 60 %, bottom = 100 %
                const widthPct = n > 1
                    ? +(60 + (idx / (n - 1)) * 40).toFixed(1)
                    : 100;
                const colClass  = colourClasses[Math.min(idx, colourClasses.length - 1)];
                const isTop     = idx === 0;
                // Stagger rise animation (ground floor rises first → bottom-up feel)
                const delay     = ((n - 1 - idx) * 0.1).toFixed(1);

                // Parapet posts only on the very top floor
                const parapetHtml = isTop ? `
                    <div class="bfloor-parapet">
                        ${Array.from({ length: 9 }, () =>
                            '<div class="bfloor-parapet-post"></div>').join('')}
                    </div>` : '';

                // Windows scaled to floor width (wider floor = more windows)
                const numWindows = Math.max(3, Math.round(widthPct / 14));
                const windowsHtml = `
                    <div class="bfloor-windows">
                        ${Array.from({ length: numWindows }, () =>
                            '<div class="bfloor-window"></div>').join('')}
                    </div>`;

                return `
                    <div class="building-floor ${colClass}"
                         data-floor-id="${floor.id}"
                         data-floor-name="${this.esc(floor.name)}"
                         style="width:${widthPct}%; animation-delay:${delay}s;">
                        ${parapetHtml}
                        <div class="bfloor-icon">${floor.icon || '🏢'}</div>
                        <div class="bfloor-info">
                            <div class="bfloor-name">${this.esc(floor.name)}</div>
                            <div class="bfloor-desc">${this.esc(floor.description || '')}</div>
                        </div>
                        <div class="bfloor-meta">${floor.room_count} rooms &middot; ${floor.table_count} tables</div>
                        <div class="bfloor-enter">&#8594;</div>
                        ${windowsHtml}
                    </div>
                `;
            }).join('');

            document.querySelectorAll('.building-floor').forEach(card => {
                card.addEventListener('click', () => {
                    this.currentFloor = {
                        id:   parseInt(card.dataset.floorId),
                        name: card.dataset.floorName
                    };
                    // Zoom clicked floor in, fade all others
                    document.querySelectorAll('.building-floor').forEach(f => {
                        f.classList.add(f === card ? 'floor-zooming' : 'floor-fade-out');
                    });
                    setTimeout(() => this.loadRooms(this.currentFloor.id), 430);
                });
            });
        } catch (e) {
            document.getElementById('floors-grid').innerHTML =
                '<p class="empty-msg">Could not load floors. Is XAMPP running?</p>';
        }
    }

    /* ========================================================
       STAGE 2: ROOMS
    ======================================================== */
    async loadRooms(floorId) {
        this.goToStage('rooms');
        const container = document.getElementById('room-plan-container');
        container.innerHTML = '<p class="loading-msg">Loading rooms…</p>';

        try {
            const data = await this.api({ action: 'get_rooms', floor_id: floorId });
            if (!data.success || !data.rooms.length) {
                container.innerHTML = '<p class="empty-msg">No rooms on this floor.</p>';
                return;
            }

            // Compute canvas size based on room extents
            let maxX = 0, maxY = 0;
            data.rooms.forEach(r => {
                maxX = Math.max(maxX, r.x + r.width + 30);
                maxY = Math.max(maxY, r.y + r.height + 30);
            });

            container.innerHTML = `
                <div class="room-canvas" style="width:${maxX}px; height:${maxY}px; position:relative;">
                    ${data.rooms.map((room, idx) => `
                        <div class="room room-clickable"
                             data-room-id="${room.id}"
                             data-room-name="${this.esc(room.name)}"
                             style="left:${room.x}px; top:${room.y}px; width:${room.width}px; height:${room.height}px; background:${room.color}; animation-delay:${idx * 0.07}s;"
                        >
                            <div class="room-label">${this.esc(room.name)}</div>
                            <div class="room-meta">${room.table_count} tables</div>
                            <div class="room-click-hint">Click to enter →</div>
                        </div>
                    `).join('')}
                </div>
            `;

            document.querySelectorAll('.room-clickable').forEach(el => {
                el.addEventListener('click', () => {
                    this.currentRoom = {
                        id:   parseInt(el.dataset.roomId),
                        name: el.dataset.roomName
                    };
                    el.classList.add('room-zoom-out');
                    setTimeout(() => this.loadTables(this.currentRoom.id), 300);
                });
            });
        } catch (e) {
            container.innerHTML = '<p class="empty-msg">Error loading rooms.</p>';
        }
    }

    /* ========================================================
       STAGE 3: TABLES
    ======================================================== */
    async loadTables(roomId) {
        this.goToStage('tables');
        const container = document.getElementById('table-plan-container');
        container.innerHTML = '<p class="loading-msg">Loading tables…</p>';

        const dateStr  = this.selectedDate ? this.selectedDate.toISOString().split('T')[0] : null;
        const timeSlot = this.selectedTime || null;
        const party    = parseInt(document.getElementById('party-size').value) || 0;

        try {
            const data = await this.api({
                action:     'get_tables',
                room_id:    roomId,
                date:       dateStr,
                time_slot:  timeSlot,
                party_size: party
            });
            if (!data.success || !data.tables.length) {
                container.innerHTML = '<p class="empty-msg">No tables in this room.</p>';
                return;
            }

            // Compute canvas size
            let maxX = 0, maxY = 0;
            data.tables.forEach(t => {
                maxX = Math.max(maxX, t.x + t.size + 30);
                maxY = Math.max(maxY, t.y + t.size + 30);
            });

            // Room background box
            let html = `<div class="table-canvas" style="width:${Math.max(maxX, 500)}px; height:${Math.max(maxY, 300)}px; position:relative;">
                <div class="room-bg-label">📍 ${this.esc(this.currentRoom?.name || 'Room')}</div>
            `;

            data.tables.forEach((t, idx) => {
                const status = t.status || 'available';
                const shape  = t.shape === 'rectangle' ? 'rectangle' : '';
                const title  = status === 'booked'
                    ? (t.too_small ? `Too small for party of ${party}` : 'Already booked')
                    : `Table ${t.table_code} — ${t.capacity} seats`;
                html += `
                    <div class="table ${shape} ${status}"
                         style="left:${t.x}px; top:${t.y}px; width:${t.size}px; height:${t.size}px; animation-delay:${idx * 0.05}s;"
                         data-table-id="${t.id}"
                         data-table-code="${this.esc(t.table_code)}"
                         data-capacity="${t.capacity}"
                         data-status="${status}"
                         title="${title}">
                        <span class="table-label">${this.esc(t.table_code)}</span>
                        <span class="table-cap">${t.capacity}👤</span>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;

            document.querySelectorAll('.table').forEach(el => {
                el.addEventListener('click', () => this.selectTable(el));
            });

            if (!dateStr || !timeSlot) {
                this.showNotification('Tip', 'Select a date and time to see real-time table availability.', 'warning');
            }
        } catch (e) {
            container.innerHTML = '<p class="empty-msg">Error loading tables.</p>';
        }
    }

    selectTable(element) {
        if (element.dataset.status === 'booked') {
            element.classList.add('table-shake');
            setTimeout(() => element.classList.remove('table-shake'), 500);
            this.showNotification('Table Unavailable', 'This table is already booked or too small for your party.', 'error');
            return;
        }
        if (!this.selectedDate || !this.selectedTime) {
            this.showNotification('Selection Required', 'Please select a date and time first.', 'warning');
            return;
        }

        document.querySelectorAll('.table').forEach(t => t.classList.remove('selected'));
        element.classList.remove('available');
        element.classList.add('selected');

        this.selectedTable = {
            id:       parseInt(element.dataset.tableId),
            code:     element.dataset.tableCode,
            capacity: parseInt(element.dataset.capacity)
        };

        const info    = document.getElementById('selected-table-info');
        const details = document.getElementById('table-details');
        details.innerHTML = `
            <strong>Floor:</strong> ${this.esc(this.currentFloor?.name || '')}<br>
            <strong>Room:</strong> ${this.esc(this.currentRoom?.name || '')}<br>
            <strong>Table:</strong> ${this.esc(this.selectedTable.code)}<br>
            <strong>Capacity:</strong> ${this.selectedTable.capacity} people<br>
            <strong>Date:</strong> ${this.selectedDate.toLocaleDateString()}<br>
            <strong>Time:</strong> ${this.selectedTime}
        `;
        info.style.display = 'block';
        info.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ========================================================
       REFRESH AVAILABILITY (real-time)
    ======================================================== */
    async refreshAvailability() {
        if (this.stage === 'tables' && this.currentRoom) {
            await this.loadTables(this.currentRoom.id);
        }
        this.updateTimeSlots();
    }

    /* ========================================================
       BOOKING CONFIRMATION
    ======================================================== */
    async confirmBooking() {
        const name    = document.getElementById('customer-name').value.trim();
        const email   = document.getElementById('customer-email').value.trim();
        const phone   = document.getElementById('customer-phone').value.trim();
        const party   = document.getElementById('party-size').value;
        const requests= document.getElementById('special-requests').value.trim();

        if (!name || !email || !phone) {
            this.showNotification('Missing Info', 'Please fill in name, email, and phone.', 'error');
            return;
        }
        if (!this.selectedDate || !this.selectedTime || !this.selectedTable) {
            this.showNotification('Incomplete', 'Please select date, time, floor, room, and table.', 'error');
            return;
        }

        const btn = document.getElementById('confirm-booking-btn');
        btn.disabled = true;
        btn.textContent = 'Confirming…';

        try {
            const data = await this.api({
                action:           'create_reservation',
                customer_name:    name,
                customer_email:   email,
                customer_phone:   phone,
                date:             this.selectedDate.toISOString().split('T')[0],
                time_slot:        this.selectedTime,
                table_id:         this.selectedTable.id,
                party_size:       party,
                special_requests: requests
            });

            if (data.success) {
                this.showConfirmation(data.reservation);
                this.resetBookingForm();
                this.refreshAvailability();
            } else {
                this.showNotification('Booking Failed', data.error || 'Could not create reservation.', 'error');
            }
        } catch (e) {
            this.showNotification('Error', 'Network error. Is XAMPP running?', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Reservation';
        }
    }

    showConfirmation(reservation) {
        document.getElementById('confirmation-details').innerHTML = `
            <p><strong>Confirmation #:</strong> ${this.esc(reservation.confirmation_code)}</p>
            <p><strong>Name:</strong> ${this.esc(reservation.customer_name)}</p>
            <p><strong>Date:</strong> ${this.esc(reservation.reservation_date)}</p>
            <p><strong>Time:</strong> ${this.esc(reservation.time_slot)}</p>
            <p><strong>Table:</strong> ${this.esc(reservation.table_code)}</p>
            <p><strong>Party Size:</strong> ${reservation.party_size} people</p>
        `;
        document.getElementById('confirmation-modal').classList.add('show');
    }

    resetBookingForm() {
        ['customer-name','customer-email','customer-phone','special-requests'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('selected-table-info').style.display = 'none';
        this.selectedTable = null;
        this.goToStage('floors');
        this.loadFloors();
    }

    /* ========================================================
       USER RESERVATIONS
    ======================================================== */
    async loadMyReservations() {
        const container = document.getElementById('reservations-list');
        if (!this.currentUser) {
            container.innerHTML = '<p style="text-align:center;padding:40px;color:#7f8c8d;">Please login to view your reservations.</p>';
            return;
        }
        container.innerHTML = '<p style="text-align:center;padding:20px;color:#7f8c8d;">Loading…</p>';
        try {
            const data = await this.api({ action: 'get_user_reservations', email: this.currentUser.email });
            if (data.success) this.displayReservations(data.reservations, 'reservations-list');
        } catch (e) { /* ignore */ }
    }

    displayReservations(reservations, containerId) {
        const container = document.getElementById(containerId);
        if (!reservations.length) {
            container.innerHTML = '<p style="text-align:center;padding:40px;color:#7f8c8d;">No reservations found.</p>';
            return;
        }
        container.innerHTML = reservations.map(res => `
            <div class="reservation-card">
                <div class="reservation-info">
                    <h4>${this.esc(res.customer_name)} — ${res.party_size} people</h4>
                    <p><strong>Date:</strong> ${res.reservation_date} | <strong>Time:</strong> ${this.esc(res.time_slot)}</p>
                    <p><strong>Location:</strong> ${this.esc(res.floor_name)} › ${this.esc(res.room_name)} › Table ${this.esc(res.table_code)}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${res.status}">${res.status}</span></p>
                    <p><strong>Ref:</strong> ${this.esc(res.confirmation_code)}</p>
                </div>
                <div class="reservation-actions">
                    ${res.status === 'confirmed' ? `
                        <button class="btn btn-small btn-danger"
                                onclick="app.cancelReservation(${res.id}, '${this.esc(res.customer_email)}')">
                            Cancel
                        </button>` : ''}
                </div>
            </div>
        `).join('');
    }

    async cancelReservation(id, email) {
        if (!confirm('Are you sure you want to cancel this reservation?')) return;
        try {
            const data = await this.api({ action: 'cancel_reservation', id, email });
            if (data.success) {
                this.showNotification('Cancelled', 'Reservation cancelled successfully.', 'success');
                this.loadMyReservations();
            } else {
                this.showNotification('Error', data.error || 'Could not cancel.', 'error');
            }
        } catch (e) { /* ignore */ }
    }

    /* ========================================================
       WAITLIST
    ======================================================== */
    async joinWaitlist() {
        const name  = document.getElementById('waitlist-name').value.trim();
        const email = document.getElementById('waitlist-email').value.trim();
        const phone = document.getElementById('waitlist-phone').value.trim();
        const party = document.getElementById('waitlist-party-size').value;
        const date  = document.getElementById('waitlist-date').value;
        const time  = document.getElementById('waitlist-time').value;

        if (!name || !email || !phone) {
            this.showNotification('Missing Info', 'Please fill in name, email, and phone.', 'error');
            return;
        }
        try {
            const data = await this.api({
                action:         'add_to_waitlist',
                name, email, phone,
                party_size:     party,
                preferred_date: date,
                preferred_time: time
            });
            if (data.success) {
                this.showNotification('Added!', data.message, 'success');
                ['waitlist-name','waitlist-email','waitlist-phone'].forEach(id => {
                    document.getElementById(id).value = '';
                });
                this.loadWaitlist();
            } else {
                this.showNotification('Error', data.error, 'error');
            }
        } catch (e) { /* ignore */ }
    }

    async loadWaitlist() {
        try {
            const data = await this.api({ action: 'get_waitlist' });
            if (data.success) this.displayWaitlist(data.waitlist);
        } catch (e) { /* ignore */ }
    }

    displayWaitlist(waitlist) {
        const container = document.getElementById('waitlist-display');
        if (!waitlist.length) {
            container.innerHTML = '<p style="color:#7f8c8d;font-size:13px;">No one on the waitlist right now.</p>';
            return;
        }
        container.innerHTML = waitlist.map((item, i) => `
            <div class="waitlist-item">
                <h5>#${i + 1} — ${this.esc(item.name)} (${item.party_size} people)</h5>
                <p>Preferred: ${item.preferred_date || 'Any date'} ${this.esc(item.preferred_time || '')}</p>
                <p>Added: ${new Date(item.created_at).toLocaleString()}</p>
                <span class="status-badge status-${item.status}">${item.status}</span>
            </div>
        `).join('');
    }

    /* ========================================================
       ADMIN
    ======================================================== */
    async loadAdminData() {
        if (!this.currentUser || this.currentUser.role !== 'admin') return;
        this.loadAdminStats();
        this.loadAdminReservations();
        this.loadAdminWaitlist();
        this.loadAdminTables();
    }

    async loadAdminStats() {
        try {
            const data = await this.api({ action: 'get_stats' });
            if (data.success) {
                document.getElementById('stat-today').textContent     = data.today_bookings;
                document.getElementById('stat-tables').textContent    = data.total_tables;
                document.getElementById('stat-occupancy').textContent = data.occupancy_pct + '%';
                document.getElementById('stat-waitlist').textContent  = data.waiting_count;
            }
        } catch (e) { /* ignore */ }
    }

    async loadAdminReservations() {
        try {
            const data = await this.api({ action: 'get_all_reservations' });
            if (data.success) {
                const container = document.getElementById('admin-reservations-list');
                if (!data.reservations.length) {
                    container.innerHTML = '<p style="text-align:center;padding:20px;color:#7f8c8d;">No reservations yet.</p>';
                    return;
                }
                container.innerHTML = data.reservations.map(res => `
                    <div class="reservation-card">
                        <div class="reservation-info">
                            <h4>${this.esc(res.customer_name)} — ${res.party_size} people</h4>
                            <p>${res.reservation_date} ${this.esc(res.time_slot)} | ${this.esc(res.floor_name)} › ${this.esc(res.room_name)} › Table ${this.esc(res.table_code)}</p>
                            <p>📧 ${this.esc(res.customer_email)} | 📞 ${this.esc(res.customer_phone)}</p>
                            <p>Ref: ${this.esc(res.confirmation_code)} | <span class="status-badge status-${res.status}">${res.status}</span></p>
                        </div>
                        <div class="reservation-actions">
                            ${res.status === 'confirmed' ? `
                                <button class="btn btn-small" onclick="app.adminUpdateReservation(${res.id},'completed')">✓ Done</button>
                                <button class="btn btn-small btn-danger" onclick="app.adminUpdateReservation(${res.id},'cancelled')">✗ Cancel</button>
                            ` : ''}
                            <button class="btn btn-small btn-danger" onclick="app.adminDeleteReservation(${res.id})">🗑</button>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) { /* ignore */ }
    }

    async loadAdminWaitlist() {
        try {
            const data = await this.api({ action: 'get_waitlist' });
            if (data.success) {
                const container = document.getElementById('admin-waitlist-list');
                if (!data.waitlist.length) {
                    container.innerHTML = '<p style="text-align:center;padding:20px;color:#7f8c8d;">Waitlist is empty.</p>';
                    return;
                }
                container.innerHTML = data.waitlist.map((item, i) => `
                    <div class="reservation-card">
                        <div class="reservation-info">
                            <h4>#${i+1} — ${this.esc(item.name)} (${item.party_size} people)</h4>
                            <p>📧 ${this.esc(item.email)} | 📞 ${this.esc(item.phone)}</p>
                            <p>Preferred: ${item.preferred_date || 'Any'} ${this.esc(item.preferred_time || '')}</p>
                            <p>Added: ${new Date(item.created_at).toLocaleString()} | <span class="status-badge status-${item.status}">${item.status}</span></p>
                        </div>
                        <div class="reservation-actions">
                            <button class="btn btn-small btn-primary" onclick="app.notifyWaitlist(${item.id})">📧 Notify</button>
                            <button class="btn btn-small btn-danger" onclick="app.removeFromWaitlist(${item.id})">Remove</button>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) { /* ignore */ }
    }

    async loadAdminTables() {
        try {
            const floorData = await this.api({ action: 'get_floors' });
            if (!floorData.success) return;
            let html = '';
            for (const floor of floorData.floors) {
                const roomData = await this.api({ action: 'get_rooms', floor_id: floor.id });
                if (!roomData.success) continue;
                html += `<div class="admin-floor-section"><h4>${this.esc(floor.name)}</h4>`;
                for (const room of roomData.rooms) {
                    const tableData = await this.api({ action: 'get_tables', room_id: room.id });
                    if (!tableData.success) continue;
                    html += `<div class="admin-room-section"><strong>${this.esc(room.name)}</strong><div class="admin-table-grid">`;
                    tableData.tables.forEach(t => {
                        html += `
                            <div class="admin-table-chip ${t.is_active ? 'active' : 'inactive'}">
                                ${this.esc(t.table_code)} (${t.capacity}👤)
                                <button class="toggle-btn" onclick="app.toggleTable(${t.id})">
                                    ${t.is_active ? 'Disable' : 'Enable'}
                                </button>
                            </div>`;
                    });
                    html += '</div></div>';
                }
                html += '</div>';
            }
            document.getElementById('admin-table-list').innerHTML = html || '<p>No tables found.</p>';
        } catch (e) { /* ignore */ }
    }

    async adminUpdateReservation(id, status) {
        const data = await this.api({ action: 'admin_update_reservation', id, status });
        if (data.success) {
            this.showNotification('Updated', `Reservation marked as ${status}.`, 'success');
            this.loadAdminReservations();
            this.loadAdminStats();
        }
    }

    async adminDeleteReservation(id) {
        if (!confirm('Permanently delete this reservation?')) return;
        const data = await this.api({ action: 'admin_delete_reservation', id });
        if (data.success) {
            this.showNotification('Deleted', 'Reservation deleted.', 'success');
            this.loadAdminReservations();
            this.loadAdminStats();
        }
    }

    async notifyWaitlist(id) {
        const data = await this.api({ action: 'notify_waitlist', id });
        if (data.success) {
            this.showNotification('Notified', 'Email notification sent (check email_log.txt).', 'success');
            this.loadAdminWaitlist();
        }
    }

    async removeFromWaitlist(id) {
        const data = await this.api({ action: 'remove_from_waitlist', id });
        if (data.success) {
            this.showNotification('Removed', 'Entry removed from waitlist.', 'success');
            this.loadAdminWaitlist();
            this.loadAdminStats();
        }
    }

    async toggleTable(id) {
        const data = await this.api({ action: 'toggle_table', id });
        if (data.success) {
            this.showNotification('Updated', 'Table status toggled.', 'success');
            this.loadAdminTables();
        }
    }

    switchAdminTab(btn) {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(`admin-tab-${tab}`).classList.add('active');
    }

    /* ========================================================
       AUTHENTICATION
    ======================================================== */
    showLogin() {
        document.getElementById('login-modal').classList.add('show');
        setTimeout(() => document.getElementById('login-email').focus(), 100);
    }

    async handleLogin() {
        const email    = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;
        if (!email || !password) {
            this.showNotification('Missing Credentials', 'Please enter email and password.', 'error');
            return;
        }
        try {
            const data = await this.api({ action: 'login', email, password });
            if (data.success) {
                this.currentUser = data.user;
                localStorage.setItem('restaurant_user', JSON.stringify(data.user));
                this.updateLoginUI();
                this.hideModal('login-modal');
                this.showNotification('Welcome', `Logged in as ${data.user.name}`, 'success');
            } else {
                this.showNotification('Login Failed', data.error || 'Invalid credentials.', 'error');
            }
        } catch (e) {
            this.showNotification('Error', 'Network error. Is XAMPP running?', 'error');
        }
    }

    logout() {
        this.currentUser = null;
        localStorage.removeItem('restaurant_user');
        this.api({ action: 'logout' });
        this.updateLoginUI();
        this.showNotification('Logged Out', 'You have been logged out.', 'success');
        document.querySelector('.nav-item[data-page="booking"]').click();
    }

    async checkSession() {
        const stored = localStorage.getItem('restaurant_user');
        if (stored) {
            try {
                const data = await this.api({ action: 'check_session' });
                if (data.user) {
                    this.currentUser = data.user;
                } else {
                    this.currentUser = JSON.parse(stored); // fallback
                }
            } catch (e) {
                this.currentUser = JSON.parse(stored);
            }
            this.updateLoginUI();
        }
    }

    updateLoginUI() {
        const welcomeText = document.getElementById('welcome-text');
        const loginBtn    = document.getElementById('login-btn');
        const logoutBtn   = document.getElementById('logout-btn');
        const adminNavs   = document.querySelectorAll('.admin-only');

        if (this.currentUser) {
            welcomeText.textContent = `Welcome, ${this.currentUser.name}`;
            loginBtn.style.display  = 'none';
            logoutBtn.style.display = 'inline-block';
            adminNavs.forEach(nav => nav.style.display = this.currentUser.role === 'admin' ? 'flex' : 'none');
        } else {
            welcomeText.textContent = 'Welcome, Guest';
            loginBtn.style.display  = 'inline-block';
            logoutBtn.style.display = 'none';
            adminNavs.forEach(nav => nav.style.display = 'none');
        }
    }

    /* ========================================================
       HELPERS
    ======================================================== */
    async api(payload) {
        const response = await fetch('api.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    }

    hideModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    showNotification(title, message, type = 'info') {
        const area  = document.getElementById('notification-area');
        const notif = document.createElement('div');
        notif.className = `notification ${type}`;
        notif.innerHTML = `
            <div class="notification-title">${this.esc(title)}</div>
            <div class="notification-message">${this.esc(message)}</div>
        `;
        area.appendChild(notif);
        setTimeout(() => {
            notif.style.animation = 'slideInRight 0.3s reverse';
            setTimeout(() => notif.remove(), 300);
        }, 4000);
    }

    esc(text) {
        const d = document.createElement('div');
        d.textContent = String(text ?? '');
        return d.innerHTML;
    }
}

// Boot
document.addEventListener('DOMContentLoaded', () => {
    window.app = new RestaurantApp();
});