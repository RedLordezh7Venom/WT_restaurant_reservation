<p align="center">
  <img src="https://img.icons8.com/color/144/000000/restaurant.png" alt="Restaurant Reservation System Logo">
</p>

# Restaurant Reservation System

**A simple, web-based dining reservation platform with interactive real-time floor plans and integrated email management.**

This project transforms a traditional static HTML interface into a dynamic, state-driven PHP/MySQL application. Built without complex JavaScript frameworks, it leverages pure vanilla JavaScript (ES6+), robust CSS3 animations, and a secure PDO-driven robust REST API in PHP.

---

## 📖 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Architecture](#architecture)
- [System Requirements](#system-requirements)
- [Installation Guide](#installation-guide)
    - [1. Install Prerequisites via Winget](#1-install-prerequisites-via-winget)
    - [2. Project Setup](#2-project-setup)
    - [3. Database Initialization](#3-database-initialization)
- [Email Configuration (PHPMailer)](#email-configuration-phpmailer)
- [Admin Dashboards](#admin-dashboards)
- [Contributing](#contributing)

---

## Overview

The **Restaurant Reservation System** brings an intuitive interface to dining management. Users navigate a rich 3-stage animated environment—starting from a 3D isometric building stack, diving into a specific floor, and clicking directly on interactive tables that pulse based on real-time availability.

Meanwhile, administrators have a protected dashboard spanning real-time table toggles, reservation overviews, and an actively managed waitlist with live email notifications.

---

## Key Features

- **Architectural Floor Plan**: A 3-stage visual interface (Building → Floor → Tables) featuring CSS container transforms and interactive table statuses (🟢 Available, 🔴 Booked).
- **Time/Slot Engine**: Dynamic calculation of booking capacity and available times based on SQLite/MySQL database polling.
- **Admin Command Center**: Secure control over table availability, managing live waitlists, and overriding constraints.
- **Automated Emailing**: Leverages `PHPMailer` to handle multi-method email dispatch (Gmail SMTP, Mailtrap, or local logging). Waitlist selections automatically contact customers via direct notification.
- **RESTful PHP Backbone**: Centralized `api.php` acts as the single source of truth, returning strictly formatted JSON for all client interactions.

---

## Architecture

* **Frontend**: Vanilla HTML5, CSS3 (Micro-animations, Flexbox/Grid structuring). JavaScript (ES6+ Classes and Fetch API).
* **Backend**: PHP 8.1+ REST API.
* **Database**: MySQL (using PHP Data Objects `PDO` for secure, parameterized queries).
* **Development Server**: Apache (via XAMPP).

---

## System Requirements

- **OS:** Windows 10/11 (for the automated winget setup)
- **Web Server:** Apache
- **Database:** MySQL/MariaDB
- **PHP:** > 8.1

---

## Installation Guide

Follow these steps to deploy the application on a local Windows environment.

### 1. Install Prerequisites via Winget

If you don't already have XAMPP installed, you can easily install it using the Windows Package Manager (`winget`). Open **PowerShell or Command Prompt** and run:

```powershell
# Install XAMPP (Includes Apache, MySQL, and PHP)
winget install -e --id ApacheFriends.Xampp.8.2
```

> **Note:** Make sure to launch the XAMPP Control Panel as Administrator after installation, and hit **Start** on both the **Apache** and **MySQL** modules.

### 2. Project Setup

1. Copy the entire restaurant project folder.
2. Paste it into your XAMPP root directory. By default, this is:
   ```text
   C:\xampp\htdocs\restaurant\
   ```
3. Your application is now served on `http://localhost/restaurant/`.

### 3. Database Initialization

1. Open your browser and go to **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**.
2. In the left panel, click **New** and create a new database named exactly: `restaurant_db`
3. Click on the `restaurant_db` database you just created.
4. Go to the **Import** tab at the top.
5. Click **Choose File** and select the `schema.sql` file located in your project folder.
6. Scroll down and click **Go** to generate all the tables (Floors, Rooms, Tables, Users, Reservations).

### 4. Admin Setup (First Time Only)

We temporarily generated strict hashed passwords. To sync the admin passwords to the new database:
1. Visit `http://localhost/restaurant/setup.php` in your browser.
2. Once you see the green success messages, **DELETE** the `setup.php` file for security.

---

## Email Configuration (PHPMailer)

Out of the box, the system logs emails to a local `email_log.txt` file for easy demo-ing. However, it integrates robustly with SMTP.

To send real emails:
1. Open `mailer.php`.
2. Locate the Configuration block at the top.
3. Uncomment the setup for **Gmail SMTP** or **Mailtrap**.
4. Define your `SMTP_USER` and `SMTP_PASS` (e.g., your 16-character Google App Password).
   
> See the included `README_EMAIL.txt` for detailed, step-by-step instructions on bypassing 2FA and generating SMTP credentials.

---

## Admin Dashboards

To access the back-end capabilities, click the User profile in the top-right header:

- **Username:** `admin@restaurant.com`
- **Password:** `admin123`

The interface will transform to unveil:
- **Global Overview Stats:** Todays total bookings, capacity metrics.
- **Reservations Panel:** Edit and finalize incoming requests.
- **Table Control:** Quickly flip a table from Available to Blocked.
- **Waitlist Control:** Notify parties precisely when tables become responsive, dropping an automated email.

---

## Contributing

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<br>
<p align="center">
  <i>Simplicity is the ultimate sophistication.</i>
</p>
