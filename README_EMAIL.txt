=====================================================
  EMAIL SETUP GUIDE — Restaurant Reservation System
=====================================================

The system supports 3 email methods. Follow ONE of the
options below, then open mailer.php and fill in the
credentials at the top of the file.

=====================================================
OPTION A — MAILTRAP (Easiest, 2 minutes)
   "Catches" emails in a browser inbox.
   No real email is sent. Perfect for a demo/lab.
=====================================================

1. Go to: https://mailtrap.io  →  Sign up (free)
2. Login → Email Testing → Inboxes → "My Inbox"
3. Click "Show Credentials" → choose "PHP" tab
4. Copy the SMTP settings shown

5. Open mailer.php and change the top section to:

   define('SMTP_METHOD', 'mailtrap');
   define('SMTP_HOST',   'sandbox.smtp.mailtrap.io');
   define('SMTP_PORT',   2525);
   define('SMTP_USER',   'paste_user_from_mailtrap');
   define('SMTP_PASS',   'paste_pass_from_mailtrap');
   define('SMTP_SECURE', 'tls');

6. Download PHPMailer (see step below — required for SMTP)

7. Make a booking → check your Mailtrap inbox!

=====================================================
OPTION B — GMAIL SMTP (Real emails, 10 minutes)
   Sends actual emails to any address.
=====================================================

STEP 1: Enable Gmail App Password
   a. Go to: myaccount.google.com
   b. Security → 2-Step Verification → turn ON (required)
   c. Security → App Passwords
   d. Select "Mail" and "Windows Computer" → Generate
   e. Copy the 16-character password shown (e.g. "abcd efgh ijkl mnop")

STEP 2: Open mailer.php and update:

   define('SMTP_METHOD', 'gmail');
   define('SMTP_HOST',   'smtp.gmail.com');
   define('SMTP_PORT',   587);
   define('SMTP_USER',   'youremail@gmail.com');   ← your Gmail
   define('SMTP_PASS',   'abcd efgh ijkl mnop');   ← App Password
   define('SMTP_SECURE', 'tls');

STEP 3: Download PHPMailer (see below)

=====================================================
DOWNLOADING PHPMAILER (required for both options)
=====================================================

EASY METHOD (no Composer needed):

1. Go to: https://github.com/PHPMailer/PHPMailer/releases
2. Download the latest Source code (zip)
3. Open the zip — find the "src" folder inside
4. Copy the "src" folder to your project
5. Rename it to "PHPMailer"
   Final path should be: restaurant/PHPMailer/PHPMailer.php

Your project folder should look like:
  restaurant/
    PHPMailer/
      Exception.php    ← must exist
      PHPMailer.php    ← must exist
      SMTP.php         ← must exist
    api.php
    mailer.php
    index.php
    ...

The system auto-detects PHPMailer. Once the files
are in place, emails will be sent via SMTP automatically.

=====================================================
OPTION C — LOG ONLY (current default)
   No real email, content saved to email_log.txt
=====================================================

Open mailer.php and set:

   define('SMTP_METHOD', 'log');

Then open email_log.txt in the project folder to see
all email content after making a booking.

=====================================================
CHECKING IF IT WORKS
=====================================================

After setup:
1. Make a test reservation
2. Check email_log.txt — it shows SENT ✅ or ERROR ❌
3. For Mailtrap: open your inbox at mailtrap.io
4. For Gmail: check the actual recipient inbox

If you get an error, the most common causes are:
- Wrong App Password (must be 16 chars, no spaces)
- 2FA not enabled on Gmail (required for App Passwords)
- PHPMailer files not in the right folder
- Mailtrap credentials copy-pasted incorrectly

=====================================================
