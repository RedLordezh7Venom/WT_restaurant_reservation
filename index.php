<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Table Reservation System</title>
    <meta name="description" content="Real-time restaurant table booking system with interactive floor plan, calendar, and waitlist management.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="container">
    <!-- Header -->
    <div id="header">
        <div class="header-left">
            <h1>🍽️ Restaurant Reservations</h1>
            <span class="tagline">Real-time table booking &amp; management</span>
        </div>
        <div class="header-right">
            <div id="user-info">
                <span id="welcome-text">Welcome, Guest</span>
                <button id="login-btn" class="btn btn-header">Login</button>
                <button id="logout-btn" class="btn btn-header" style="display:none;">Logout</button>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div id="main-layout">
        <!-- Sidebar Navigation -->
        <div id="sidebar">
            <div class="sidebar-header">Navigation</div>
            <ul class="nav-menu">
                <li class="nav-item active" data-page="booking">
                    <span class="nav-icon">📅</span>
                    <span class="nav-text">Book a Table</span>
                </li>
                <li class="nav-item" data-page="my-reservations">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">My Reservations</span>
                </li>
                <li class="nav-item" data-page="waitlist">
                    <span class="nav-icon">⏰</span>
                    <span class="nav-text">Waitlist</span>
                </li>
                <li class="nav-item admin-only" data-page="admin" style="display:none;">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Admin Panel</span>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="info-box">
                    <div class="info-title">Restaurant Hours</div>
                    <div class="info-content">
                        Mon–Fri: 11:00 AM – 10:00 PM<br>
                        Sat–Sun: 10:00 AM – 11:00 PM
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div id="content-area">

            <!-- ===== BOOKING PAGE ===== -->
            <div id="page-booking" class="page active">
                <div class="content-header">
                    <h2>Reserve Your Table</h2>
                    <p>Select date &amp; time, then choose your floor, room, and table from our interactive floor plan</p>
                </div>

                <div class="booking-layout">
                    <!-- Left Panel: Calendar & Time Selection -->
                    <div class="booking-panel">
                        <div class="panel-section">
                            <h3>Select Date</h3>
                            <div id="calendar-container"></div>
                        </div>

                        <div class="panel-section">
                            <h3>Select Time Slot</h3>
                            <div id="time-slots"></div>
                        </div>

                        <div class="panel-section">
                            <h3>Party Size</h3>
                            <select id="party-size" class="form-control">
                                <option value="1">1 Person</option>
                                <option value="2" selected>2 People</option>
                                <option value="3">3 People</option>
                                <option value="4">4 People</option>
                                <option value="5">5 People</option>
                                <option value="6">6 People</option>
                                <option value="7">7 People</option>
                                <option value="8">8+ People</option>
                            </select>
                        </div>

                        <div class="panel-section">
                            <h3>Your Details</h3>
                            <input type="text"  id="customer-name"  class="form-control" placeholder="Full Name" required>
                            <input type="email" id="customer-email" class="form-control" placeholder="Email Address" required>
                            <input type="tel"   id="customer-phone" class="form-control" placeholder="Phone Number" required>
                            <textarea id="special-requests" class="form-control" placeholder="Special requests (optional)" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Right Panel: Interactive Floor Plan (3-stage) -->
                    <div class="floor-plan-panel">
                        <div class="floor-plan-header">
                            <h3>Interactive Floor Plan</h3>
                            <div class="legend">
                                <span class="legend-item"><span class="legend-color available"></span> Available</span>
                                <span class="legend-item"><span class="legend-color selected"></span> Selected</span>
                                <span class="legend-item"><span class="legend-color booked"></span> Booked</span>
                            </div>
                        </div>

                        <!-- Breadcrumb -->
                        <div id="floor-breadcrumb" class="floor-breadcrumb" style="display:none;">
                            <button id="bc-building" class="bc-btn">🏢 Building</button>
                            <span class="bc-sep">›</span>
                            <button id="bc-floor" class="bc-btn" style="display:none;"></button>
                            <span id="bc-floor-sep" class="bc-sep" style="display:none;">›</span>
                            <span id="bc-room" class="bc-room" style="display:none;"></span>
                        </div>

                        <!-- Stage 1: Floor Selector -->
                        <div id="stage-floors" class="floor-stage active-stage">
                            <p class="stage-hint">👆 Select a floor to see available rooms</p>
                            <div id="floors-grid" class="floors-grid">
                                <!-- Floor cards injected by JS -->
                                <div class="floor-card-loading">Loading floors…</div>
                            </div>
                        </div>

                        <!-- Stage 2: Room View -->
                        <div id="stage-rooms" class="floor-stage">
                            <div id="room-plan-container">
                                <!-- Room boxes injected by JS -->
                            </div>
                        </div>

                        <!-- Stage 3: Table View -->
                        <div id="stage-tables" class="floor-stage">
                            <div id="table-plan-container">
                                <!-- Tables injected by JS -->
                            </div>
                        </div>

                        <!-- Selected Table Confirmation Panel -->
                        <div class="selected-table-info" id="selected-table-info" style="display:none;">
                            <h4>Selected Table</h4>
                            <p id="table-details"></p>
                            <button id="confirm-booking-btn" class="btn btn-primary btn-large">Confirm Reservation</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== MY RESERVATIONS PAGE ===== -->
            <div id="page-my-reservations" class="page">
                <div class="content-header">
                    <h2>My Reservations</h2>
                    <p>View and manage your upcoming reservations</p>
                </div>
                <div id="reservations-list"></div>
            </div>

            <!-- ===== WAITLIST PAGE ===== -->
            <div id="page-waitlist" class="page">
                <div class="content-header">
                    <h2>Waitlist</h2>
                    <p>Join the waitlist when no tables are available — we'll email you when a spot opens</p>
                </div>

                <div class="waitlist-container">
                    <div class="waitlist-form">
                        <h3>Add to Waitlist</h3>
                        <input type="text"  id="waitlist-name"       class="form-control" placeholder="Full Name">
                        <input type="email" id="waitlist-email"      class="form-control" placeholder="Email Address">
                        <input type="tel"   id="waitlist-phone"      class="form-control" placeholder="Phone Number">
                        <select id="waitlist-party-size" class="form-control">
                            <option value="1">1 Person</option>
                            <option value="2" selected>2 People</option>
                            <option value="3">3 People</option>
                            <option value="4">4 People</option>
                            <option value="5">5 People</option>
                            <option value="6">6 People</option>
                        </select>
                        <input type="date"  id="waitlist-date"       class="form-control" placeholder="Preferred Date">
                        <select id="waitlist-time" class="form-control">
                            <option value="">Any time</option>
                        </select>
                        <button id="join-waitlist-btn" class="btn btn-primary">Join Waitlist</button>
                    </div>

                    <div class="current-waitlist">
                        <h3>Current Waitlist</h3>
                        <div id="waitlist-display"></div>
                    </div>
                </div>
            </div>

            <!-- ===== ADMIN PANEL PAGE ===== -->
            <div id="page-admin" class="page">
                <div class="content-header">
                    <h2>Admin Dashboard</h2>
                    <p>Manage reservations, floor plan, and waitlist</p>
                </div>

                <!-- Stats Bar -->
                <div class="admin-stats" id="admin-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="stat-today">–</div>
                        <div class="stat-label">Today's Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-tables">–</div>
                        <div class="stat-label">Total Tables</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-occupancy">–</div>
                        <div class="stat-label">Today's Occupancy</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-waitlist">–</div>
                        <div class="stat-label">On Waitlist</div>
                    </div>
                </div>

                <div class="admin-tabs">
                    <button class="tab-btn active" data-tab="all-reservations">All Reservations</button>
                    <button class="tab-btn" data-tab="table-management">Table Management</button>
                    <button class="tab-btn" data-tab="waitlist-management">Waitlist</button>
                </div>

                <div id="admin-tab-all-reservations" class="admin-tab-content active">
                    <div id="admin-reservations-list"></div>
                </div>

                <div id="admin-tab-table-management" class="admin-tab-content">
                    <h3>Floor &amp; Table Status</h3>
                    <div id="admin-table-list"></div>
                </div>

                <div id="admin-tab-waitlist-management" class="admin-tab-content">
                    <div id="admin-waitlist-list"></div>
                </div>
            </div>

        </div><!-- /content-area -->
    </div><!-- /main-layout -->

    <!-- Footer -->
    <div id="footer">
        <span>© 2025 Restaurant Reservations | Real-time booking system | Powered by PHP + MySQL</span>
    </div>
</div><!-- /container -->

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Login</h2>
            <button class="close-btn" id="close-login">×</button>
        </div>
        <div class="modal-body">
            <input type="email"    id="login-email"    class="form-control" placeholder="Email Address">
            <input type="password" id="login-password" class="form-control" placeholder="Password">
            <div class="modal-footer">
                <button id="submit-login" class="btn btn-primary">Login</button>
            </div>
            <p class="help-text">Demo: <strong>admin@restaurant.com</strong> / <strong>admin123</strong> (Admin)<br>
            Or: <strong>user@test.com</strong> / <strong>user123</strong> (User)</p>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reservation Confirmed! 🎉</h2>
            <button class="close-btn" id="close-confirmation">×</button>
        </div>
        <div class="modal-body">
            <div class="confirmation-icon">✓</div>
            <div id="confirmation-details"></div>
            <p class="confirmation-message">A confirmation email has been sent.<br>Please check your email for confirmation details.</p>
            <div class="modal-footer">
                <button id="close-confirmation-btn" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Area -->
<div id="notification-area"></div>

<script src="app.js"></script>
</body>
</html>
