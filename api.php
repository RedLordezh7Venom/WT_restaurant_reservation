<?php
/**
 * Restaurant Reservation System - REST API
 * All requests are POST with JSON body: { "action": "...", ...params }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

// Start session for auth
session_start();

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

$db = getDB();

try {
    switch ($action) {

        // ---- AUTH -------------------------------------------------------
        case 'login':
            apiLogin($db, $input);
            break;
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
        case 'check_session':
            echo json_encode([
                'success' => true,
                'user'    => $_SESSION['user'] ?? null
            ]);
            break;

        // ---- FLOOR PLAN -------------------------------------------------
        case 'get_floors':
            getFloors($db);
            break;
        case 'get_rooms':
            getRooms($db, $input);
            break;
        case 'get_tables':
            getTables($db, $input);
            break;
        case 'get_table_availability':
            getTableAvailability($db, $input);
            break;
        case 'get_available_slots':
            getAvailableSlots($db, $input);
            break;

        // ---- RESERVATIONS -----------------------------------------------
        case 'create_reservation':
            createReservation($db, $input);
            break;
        case 'cancel_reservation':
            cancelReservation($db, $input);
            break;
        case 'get_user_reservations':
            getUserReservations($db, $input);
            break;
        case 'get_all_reservations':
            getAllReservations($db);
            break;
        case 'admin_update_reservation':
            adminUpdateReservation($db, $input);
            break;
        case 'admin_delete_reservation':
            adminDeleteReservation($db, $input);
            break;

        // ---- WAITLIST ---------------------------------------------------
        case 'add_to_waitlist':
            addToWaitlist($db, $input);
            break;
        case 'get_waitlist':
            getWaitlist($db);
            break;
        case 'remove_from_waitlist':
            removeFromWaitlist($db, $input);
            break;
        case 'notify_waitlist':
            notifyWaitlist($db, $input);
            break;

        // ---- ADMIN FLOOR MANAGEMENT ------------------------------------
        case 'toggle_table':
            toggleTable($db, $input);
            break;
        case 'get_stats':
            getStats($db);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ==========================================================================
// AUTH
// ==========================================================================

function apiLogin(PDO $db, array $input): void {
    $email    = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Email and password required.']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Support both hashed and plain passwords (plain for dev convenience)
    $valid = $user && (
        password_verify($password, $user['password']) ||
        $password === $user['password']
    );

    if (!$valid) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
        return;
    }

    $userData = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
    $_SESSION['user'] = $userData;

    echo json_encode(['success' => true, 'user' => $userData]);
}

// ==========================================================================
// FLOOR PLAN
// ==========================================================================

function getFloors(PDO $db): void {
    $stmt = $db->query("SELECT * FROM floors WHERE is_active = 1 ORDER BY display_order");
    $floors = $stmt->fetchAll();

    // Count tables and rooms per floor
    foreach ($floors as &$floor) {
        $s = $db->prepare("SELECT COUNT(*) FROM rooms WHERE floor_id = ? AND is_active = 1");
        $s->execute([$floor['id']]);
        $floor['room_count'] = (int)$s->fetchColumn();

        $s2 = $db->prepare(
            "SELECT COUNT(*) FROM `tables` t 
             JOIN rooms r ON t.room_id = r.id 
             WHERE r.floor_id = ? AND t.is_active = 1"
        );
        $s2->execute([$floor['id']]);
        $floor['table_count'] = (int)$s2->fetchColumn();
    }
    unset($floor);

    echo json_encode(['success' => true, 'floors' => $floors]);
}

function getRooms(PDO $db, array $input): void {
    $floorId = (int)($input['floor_id'] ?? 0);
    if (!$floorId) {
        echo json_encode(['success' => false, 'error' => 'floor_id required']);
        return;
    }
    $stmt = $db->prepare("SELECT * FROM rooms WHERE floor_id = ? AND is_active = 1");
    $stmt->execute([$floorId]);
    $rooms = $stmt->fetchAll();

    foreach ($rooms as &$room) {
        $s = $db->prepare("SELECT COUNT(*) FROM `tables` WHERE room_id = ? AND is_active = 1");
        $s->execute([$room['id']]);
        $room['table_count'] = (int)$s->fetchColumn();
    }
    unset($room);

    echo json_encode(['success' => true, 'rooms' => $rooms]);
}

function getTables(PDO $db, array $input): void {
    $roomId = (int)($input['room_id'] ?? 0);
    if (!$roomId) {
        echo json_encode(['success' => false, 'error' => 'room_id required']);
        return;
    }

    $date      = $input['date']      ?? null;
    $timeSlot  = $input['time_slot'] ?? null;
    $partySize = (int)($input['party_size'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM `tables` WHERE room_id = ? AND is_active = 1");
    $stmt->execute([$roomId]);
    $tables = $stmt->fetchAll();

    if ($date && $timeSlot) {
        // Mark booked tables
        $bookStmt = $db->prepare(
            "SELECT table_id FROM reservations 
             WHERE reservation_date = ? AND time_slot = ? AND status = 'confirmed'"
        );
        $bookStmt->execute([$date, $timeSlot]);
        $bookedIds = $bookStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as &$table) {
            $isBooked  = in_array($table['id'], $bookedIds);
            $tooSmall  = $partySize > 0 && $table['capacity'] < $partySize;
            $table['status'] = ($isBooked || $tooSmall) ? 'booked' : 'available';
            $table['is_booked'] = $isBooked ? 1 : 0;
            $table['too_small'] = $tooSmall ? 1 : 0;
        }
        unset($table);
    } else {
        foreach ($tables as &$table) {
            $table['status'] = 'available';
        }
        unset($table);
    }

    echo json_encode(['success' => true, 'tables' => $tables]);
}

function getTableAvailability(PDO $db, array $input): void {
    $date     = $input['date']      ?? null;
    $timeSlot = $input['time_slot'] ?? null;
    $roomId   = (int)($input['room_id'] ?? 0);

    if (!$date || !$timeSlot) {
        echo json_encode(['success' => false, 'error' => 'date and time_slot required']);
        return;
    }

    $qry = "SELECT r.table_id FROM reservations r
            JOIN `tables` t ON r.table_id = t.id
            WHERE r.reservation_date = ? AND r.time_slot = ? AND r.status = 'confirmed'";
    $params = [$date, $timeSlot];

    if ($roomId) {
        $qry .= " AND t.room_id = ?";
        $params[] = $roomId;
    }

    $stmt = $db->prepare($qry);
    $stmt->execute($params);
    $bookedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'bookedTableIds' => array_map('intval', $bookedIds)]);
}

function getAvailableSlots(PDO $db, array $input): void {
    $date = $input['date'] ?? null;
    if (!$date) {
        echo json_encode(['success' => false, 'error' => 'date required']);
        return;
    }

    // All possible time slots
    $allSlots = generateTimeSlots();

    // Count total active tables
    $totalStmt = $db->query("SELECT COUNT(*) FROM `tables` WHERE is_active = 1");
    $totalTables = (int)$totalStmt->fetchColumn();

    $availableSlots = [];
    foreach ($allSlots as $slot) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM reservations 
             WHERE reservation_date = ? AND time_slot = ? AND status = 'confirmed'"
        );
        $stmt->execute([$date, $slot]);
        $booked = (int)$stmt->fetchColumn();
        if ($booked < $totalTables) {
            $availableSlots[] = $slot;
        }
    }

    echo json_encode(['success' => true, 'availableSlots' => $availableSlots, 'allSlots' => $allSlots]);
}

function generateTimeSlots(): array {
    $slots = [];
    for ($hour = 11; $hour <= 21; $hour++) {
        foreach ([0, 30] as $min) {
            $h = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $m = str_pad($min, 2, '0', STR_PAD_LEFT);
            $time = new DateTime("2000-01-01 $h:$m");
            $slots[] = $time->format('g:i A');
        }
    }
    return $slots;
}

// ==========================================================================
// RESERVATIONS
// ==========================================================================

function createReservation(PDO $db, array $input): void {
    $required = ['customer_name', 'customer_email', 'customer_phone', 'date', 'time_slot', 'table_id', 'party_size'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
            return;
        }
    }

    $tableId    = (int)$input['table_id'];
    $date       = $input['date'];
    $timeSlot   = $input['time_slot'];
    $partySize  = (int)$input['party_size'];
    $name       = trim($input['customer_name']);
    $email      = trim($input['customer_email']);
    $phone      = trim($input['customer_phone']);
    $requests   = trim($input['special_requests'] ?? '');
    $userId     = $_SESSION['user']['id'] ?? null;

    // Check table exists and is active
    $tStmt = $db->prepare("SELECT t.*, r.name as room_name, f.name as floor_name 
                            FROM `tables` t 
                            JOIN rooms r ON t.room_id = r.id 
                            JOIN floors f ON r.floor_id = f.id
                            WHERE t.id = ? AND t.is_active = 1");
    $tStmt->execute([$tableId]);
    $table = $tStmt->fetch();
    if (!$table) {
        echo json_encode(['success' => false, 'error' => 'Table not found.']);
        return;
    }

    // Check table capacity
    if ($table['capacity'] < $partySize) {
        echo json_encode(['success' => false, 'error' => "Table capacity ({$table['capacity']}) is less than party size ($partySize)."]);
        return;
    }

    // Check not already booked
    $chk = $db->prepare(
        "SELECT id FROM reservations 
         WHERE table_id = ? AND reservation_date = ? AND time_slot = ? AND status = 'confirmed'"
    );
    $chk->execute([$tableId, $date, $timeSlot]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This table is already booked for that date and time.']);
        return;
    }

    // Generate confirmation code
    $confirmCode = 'RES-' . strtoupper(substr(md5(uniqid()), 0, 8));

    $stmt = $db->prepare(
        "INSERT INTO reservations 
         (user_id, table_id, reservation_date, time_slot, party_size, customer_name, customer_email, customer_phone, special_requests, status, confirmation_code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)"
    );
    $stmt->execute([$userId, $tableId, $date, $timeSlot, $partySize, $name, $email, $phone, $requests, $confirmCode]);
    $newId = $db->lastInsertId();

    $reservation = [
        'id'                => $newId,
        'customer_name'     => $name,
        'customer_email'    => $email,
        'reservation_date'  => $date,
        'time_slot'         => $timeSlot,
        'confirmation_code' => $confirmCode,
        'party_size'        => $partySize,
        'table_code'        => $table['table_code'],
    ];

    // Send confirmation email
    sendReservationConfirmation(
        array_merge($reservation, ['customer_email' => $email]),
        $table['table_code'],
        $table['floor_name'],
        $table['room_name']
    );

    echo json_encode([
        'success'     => true,
        'reservation' => $reservation,
        'message'     => 'Reservation confirmed! A confirmation email has been sent.'
    ]);
}

function cancelReservation(PDO $db, array $input): void {
    $id    = (int)($input['id'] ?? 0);
    $email = trim($input['email'] ?? '');

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Reservation ID required.']);
        return;
    }

    // Load reservation
    $stmt = $db->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if (!$res) {
        echo json_encode(['success' => false, 'error' => 'Reservation not found.']);
        return;
    }

    // Only admin or the owner can cancel
    $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
    if (!$isAdmin && $res['customer_email'] !== $email) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
        return;
    }

    $db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?")->execute([$id]);
    sendCancellationEmail($res);

    echo json_encode(['success' => true, 'message' => 'Reservation cancelled.']);
}

function getUserReservations(PDO $db, array $input): void {
    $email = trim($input['email'] ?? '');
    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'email required']);
        return;
    }

    $stmt = $db->prepare(
        "SELECT r.*, t.table_code, t.capacity, rm.name as room_name, f.name as floor_name
         FROM reservations r
         JOIN `tables` t ON r.table_id = t.id
         JOIN rooms rm ON t.room_id = rm.id
         JOIN floors f ON rm.floor_id = f.id
         WHERE r.customer_email = ?
         ORDER BY r.reservation_date DESC, r.time_slot DESC"
    );
    $stmt->execute([$email]);
    echo json_encode(['success' => true, 'reservations' => $stmt->fetchAll()]);
}

function getAllReservations(PDO $db): void {
    requireAdmin();
    $stmt = $db->query(
        "SELECT r.*, t.table_code, t.capacity, rm.name as room_name, f.name as floor_name
         FROM reservations r
         JOIN `tables` t ON r.table_id = t.id
         JOIN rooms rm ON t.room_id = rm.id
         JOIN floors f ON rm.floor_id = f.id
         ORDER BY r.reservation_date DESC, r.time_slot DESC"
    );
    echo json_encode(['success' => true, 'reservations' => $stmt->fetchAll()]);
}

function adminUpdateReservation(PDO $db, array $input): void {
    requireAdmin();
    $id     = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? 'confirmed';
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id required']); return; }
    $db->prepare("UPDATE reservations SET status = ? WHERE id = ?")->execute([$status, $id]);
    echo json_encode(['success' => true]);
}

function adminDeleteReservation(PDO $db, array $input): void {
    requireAdmin();
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id required']); return; }
    $db->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
}

// ==========================================================================
// WAITLIST
// ==========================================================================

function addToWaitlist(PDO $db, array $input): void {
    $name      = trim($input['name']       ?? '');
    $email     = trim($input['email']      ?? '');
    $phone     = trim($input['phone']      ?? '');
    $partySize = (int)($input['party_size'] ?? 2);
    $date      = $input['preferred_date']  ?? null;
    $time      = $input['preferred_time']  ?? null;

    if (!$name || !$email || !$phone) {
        echo json_encode(['success' => false, 'error' => 'Name, email, and phone required.']);
        return;
    }

    $stmt = $db->prepare(
        "INSERT INTO waitlist (name, email, phone, party_size, preferred_date, preferred_time) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $phone, $partySize, $date, $time]);
    $pos = $db->prepare("SELECT COUNT(*) FROM waitlist WHERE status = 'waiting' AND id <= ?");
    $pos->execute([$db->lastInsertId()]);
    $position = (int)$pos->fetchColumn();

    echo json_encode(['success' => true, 'position' => $position, 'message' => "Added to waitlist at position #$position."]);
}

function getWaitlist(PDO $db): void {
    $stmt = $db->query("SELECT * FROM waitlist WHERE status IN ('waiting','notified') ORDER BY created_at ASC");
    echo json_encode(['success' => true, 'waitlist' => $stmt->fetchAll()]);
}

function removeFromWaitlist(PDO $db, array $input): void {
    requireAdmin();
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id required']); return; }
    $db->prepare("UPDATE waitlist SET status = 'cancelled' WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
}

function notifyWaitlist(PDO $db, array $input): void {
    requireAdmin();
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id required']); return; }

    $stmt = $db->prepare("SELECT * FROM waitlist WHERE id = ?");
    $stmt->execute([$id]);
    $entry = $stmt->fetch();
    if (!$entry) { echo json_encode(['success' => false, 'error' => 'Entry not found']); return; }

    sendWaitlistNotification($entry);
    $db->prepare("UPDATE waitlist SET status = 'notified' WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Notification sent.']);
}

// ==========================================================================
// ADMIN UTILITIES
// ==========================================================================

function toggleTable(PDO $db, array $input): void {
    requireAdmin();
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id required']); return; }
    $db->prepare("UPDATE `tables` SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
}

function getStats(PDO $db): void {
    requireAdmin();

    $today = date('Y-m-d');

    $todayBookings = $db->prepare(
        "SELECT COUNT(*) FROM reservations WHERE reservation_date = ? AND status = 'confirmed'"
    );
    $todayBookings->execute([$today]);

    $totalTables = (int)$db->query("SELECT COUNT(*) FROM `tables` WHERE is_active = 1")->fetchColumn();

    $bookedToday = $db->prepare(
        "SELECT COUNT(DISTINCT table_id) FROM reservations WHERE reservation_date = ? AND status = 'confirmed'"
    );
    $bookedToday->execute([$today]);

    $waitingCount = (int)$db->query("SELECT COUNT(*) FROM waitlist WHERE status = 'waiting'")->fetchColumn();

    $occupancy = $totalTables > 0 ? round(((int)$bookedToday->fetchColumn() / $totalTables) * 100) : 0;

    echo json_encode([
        'success'       => true,
        'today_bookings'=> (int)$todayBookings->fetchColumn(),
        'total_tables'  => $totalTables,
        'occupancy_pct' => $occupancy,
        'waiting_count' => $waitingCount,
    ]);
}

function requireAdmin(): void {
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'error' => 'Admin access required.']);
        exit;
    }
}
