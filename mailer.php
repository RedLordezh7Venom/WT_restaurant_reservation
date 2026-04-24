<?php
/**
 * ============================================================
 *  EMAIL CONFIGURATION
 *  Choose ONE method below and fill in its credentials.
 *  Leave the others commented out.
 * ============================================================
 */

// ── METHOD 1: Gmail SMTP (real emails to any address) ────────
// Steps:
//  1. Enable 2-Step Verification on your Gmail account
//  2. Go to myaccount.google.com → Security → App Passwords
//  3. Create an App Password for "Mail" → copy the 16-char code
//  4. Paste your Gmail address and that App Password below
//  5. Download PHPMailer (see README_EMAIL.txt created below)
//
define('SMTP_METHOD',   'gmail');           // 'gmail' | 'mailtrap' | 'log'
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'prabhatkrishnaphoton@gmail.com');   // ← your Gmail
define('SMTP_PASS',     'kitg smgc nnwg kedx');   // ← 16-char App Password
define('SMTP_SECURE',   'tls');

// ── METHOD 2: Mailtrap (catches emails in browser dashboard) ─
// Steps:
//  1. Sign up free at https://mailtrap.io
//  2. Go to Inboxes → My Inbox → SMTP Settings → PHP
//  3. Copy host/port/user/pass and paste below
//  4. Uncomment the block below & comment out the Gmail block above
//
// define('SMTP_METHOD', 'mailtrap');
// define('SMTP_HOST',   'sandbox.smtp.mailtrap.io');
// define('SMTP_PORT',   2525);
// define('SMTP_USER',   'your_mailtrap_user');
// define('SMTP_PASS',   'your_mailtrap_pass');
// define('SMTP_SECURE', 'tls');

// ── METHOD 3: Log only (no email sent, shown in email_log.txt) 
// Uncomment this line and comment the blocks above if you just
// want to see email content without sending anything:
//
// define('SMTP_METHOD', 'log');

// ── Sender identity ──────────────────────────────────────────
define('FROM_EMAIL',       'noreply@restaurant.com');
define('FROM_NAME',        'Restaurant Reservations');
define('RESTAURANT_NAME',  '🍽️ Restaurant Reservations');
define('EMAIL_LOG_FILE',   __DIR__ . '/email_log.txt');

// ── PHPMailer path (if downloaded) ───────────────────────────
// After downloading PHPMailer, set this to the 'src' folder path:
define('PHPMAILER_PATH',   __DIR__ . '/PHPMailer/');

// ============================================================

/**
 * Core send function.
 * Auto-detects whether PHPMailer is available.
 * Falls back to log file if not configured or on failure.
 */
function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    // Always log the email regardless of method (useful for debugging)
    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "\n"
        . "==========================================\n"
        . "TIMESTAMP : $timestamp\n"
        . "METHOD    : " . SMTP_METHOD . "\n"
        . "TO        : $toName <$toEmail>\n"
        . "SUBJECT   : $subject\n"
        . "------------------------------------------\n"
        . strip_tags($htmlBody) . "\n"
        . "==========================================\n\n";
    file_put_contents(EMAIL_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);

    if (SMTP_METHOD === 'log') {
        return true; // log-only mode
    }

    // Try PHPMailer SMTP (if files exist)
    $phpmailerExceptionFile = PHPMAILER_PATH . 'Exception.php';
    $phpmailerFile          = PHPMAILER_PATH . 'PHPMailer.php';
    $smtpFile               = PHPMAILER_PATH . 'SMTP.php';

    if (file_exists($phpmailerFile) && file_exists($smtpFile) && file_exists($phpmailerExceptionFile)) {
        return sendViaPhpMailer($toEmail, $toName, $subject, $htmlBody);
    }

    // Fallback: native mail() — only works if a mail server is configured
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $result   = @mail($toEmail, $subject, $htmlBody, $headers);

    if (!$result) {
        // Log that PHPMailer is missing
        file_put_contents(EMAIL_LOG_FILE,
            "[WARNING] PHPMailer not found at " . PHPMAILER_PATH . "\n"
            . "         Email was NOT sent. See README_EMAIL.txt for setup.\n\n",
            FILE_APPEND | LOCK_EX);
    }
    return true; // never crash the booking flow over email
}

/**
 * Send via PHPMailer SMTP (Gmail or Mailtrap)
 */
function sendViaPhpMailer(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    require_once PHPMAILER_PATH . 'Exception.php';
    require_once PHPMAILER_PATH . 'PHPMailer.php';
    require_once PHPMAILER_PATH . 'SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host        = SMTP_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = SMTP_USER;
        $mail->Password    = SMTP_PASS;
        $mail->SMTPSecure  = SMTP_SECURE === 'tls'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port        = SMTP_PORT;
        $mail->CharSet     = 'UTF-8';

        // Sender & recipient
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();

        file_put_contents(EMAIL_LOG_FILE,
            "[" . date('Y-m-d H:i:s') . "] ✅ Email SENT via SMTP to $toEmail\n",
            FILE_APPEND | LOCK_EX);
        return true;

    } catch (\PHPMailer\PHPMailer\Exception $e) {
        file_put_contents(EMAIL_LOG_FILE,
            "[" . date('Y-m-d H:i:s') . "] ❌ SMTP ERROR: {$mail->ErrorInfo}\n",
            FILE_APPEND | LOCK_EX);
        return false;
    }
}

// ============================================================
//  EMAIL TEMPLATES (unchanged)
// ============================================================

function sendReservationConfirmation(array $reservation, string $tableName, string $floorName, string $roomName): bool {
    $subject = "✅ Reservation Confirmed – " . RESTAURANT_NAME;
    $html = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;'>
      <div style='background:linear-gradient(to right,#d35400,#c0392b);padding:30px;text-align:center;'>
        <h1 style='color:#fff;margin:0;font-size:24px;'>🍽️ Reservation Confirmed!</h1>
      </div>
      <div style='padding:30px;'>
        <p style='color:#555;'>Dear <strong>{$reservation['customer_name']}</strong>,</p>
        <p style='color:#555;'>Your reservation has been confirmed. Here are the details:</p>
        <div style='background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;border-left:4px solid #e74c3c;'>
          <table style='width:100%;border-collapse:collapse;'>
            <tr><td style='padding:8px 0;color:#7f8c8d;width:40%;'>Confirmation #</td><td style='padding:8px 0;font-weight:bold;color:#2c3e50;'>{$reservation['confirmation_code']}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Name</td><td style='padding:8px 0;color:#2c3e50;'>{$reservation['customer_name']}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Date</td><td style='padding:8px 0;color:#2c3e50;'>{$reservation['reservation_date']}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Time</td><td style='padding:8px 0;color:#2c3e50;'>{$reservation['time_slot']}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Location</td><td style='padding:8px 0;color:#2c3e50;'>{$floorName} &rsaquo; {$roomName}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Table</td><td style='padding:8px 0;color:#2c3e50;'>{$tableName}</td></tr>
            <tr><td style='padding:8px 0;color:#7f8c8d;'>Party Size</td><td style='padding:8px 0;color:#2c3e50;'>{$reservation['party_size']} people</td></tr>
          </table>
        </div>
        <p style='color:#7f8c8d;font-size:14px;'>Thank you for choosing " . RESTAURANT_NAME . ". We look forward to serving you!</p>
      </div>
      <div style='background:#2c3e50;padding:15px;text-align:center;'>
        <p style='color:#aaa;font-size:12px;margin:0;'>© 2025 " . RESTAURANT_NAME . " | To cancel, reply to this email.</p>
      </div>
    </div>";
    return sendEmail($reservation['customer_email'], $reservation['customer_name'], $subject, $html);
}

function sendCancellationEmail(array $reservation): bool {
    $subject = "❌ Reservation Cancelled – " . RESTAURANT_NAME;
    $html = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
      <div style='background:linear-gradient(to right,#7f8c8d,#2c3e50);padding:30px;text-align:center;'>
        <h1 style='color:#fff;margin:0;'>Reservation Cancelled</h1>
      </div>
      <div style='padding:30px;'>
        <p>Dear <strong>{$reservation['customer_name']}</strong>,</p>
        <p>Your reservation <strong>#{$reservation['confirmation_code']}</strong> for
           <strong>{$reservation['reservation_date']}</strong> at
           <strong>{$reservation['time_slot']}</strong> has been cancelled.</p>
        <p style='color:#7f8c8d;font-size:14px;'>If this was a mistake, please book again on our website.</p>
      </div>
    </div>";
    return sendEmail($reservation['customer_email'], $reservation['customer_name'], $subject, $html);
}

function sendWaitlistNotification(array $entry): bool {
    $subject = "🎉 A Table is Available! – " . RESTAURANT_NAME;
    $html = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
      <div style='background:linear-gradient(to right,#27ae60,#2ecc71);padding:30px;text-align:center;'>
        <h1 style='color:#fff;margin:0;'>🎉 A Table is Now Available!</h1>
      </div>
      <div style='padding:30px;'>
        <p>Dear <strong>{$entry['name']}</strong>,</p>
        <p>Great news! A table matching your preference is now available.
           Please visit our website to book immediately before it's taken.</p>
        <p style='color:#7f8c8d;font-size:14px;'>
           Party of {$entry['party_size']} | Preferred: {$entry['preferred_date']} {$entry['preferred_time']}
        </p>
        <div style='text-align:center;margin-top:25px;'>
          <a href='http://localhost/restaurant/'
             style='background:#e74c3c;color:#fff;padding:14px 30px;border-radius:6px;text-decoration:none;font-weight:bold;'>
             Book Now
          </a>
        </div>
      </div>
    </div>";
    return sendEmail($entry['email'], $entry['name'], $subject, $html);
}
