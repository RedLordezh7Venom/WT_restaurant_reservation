# Development Journey: Restaurant Reservation System

This document outlines the architectural decisions and the step-by-step process used to build a fully functional, database-driven Restuarant Reservation System from scratch.

---

## Part 1: The 15-Step Build Process

### Phase 1: Environment & Architecture
**Step 1. Planning the Architecture**
We chose a classic "LAMP" stack environment via XAMPP. This ensures the backend (PHP/MySQL) and frontend (HTML/CSS/JS) can be built rapidly and tested on a local machine without deploying to the cloud.

**Step 2. Normalizing the Relational Schema**
Designed a structured database separating `users`, `floors`, `rooms`, `tables`, `reservations`, and `waitlist` to ensure data integrity and prevent redundancies (e.g., changing a room name once updates everywhere).

**Step 3. Seeding the Database (`schema.sql`)**
Created an automated batch script to generate the tables and populate dummy data, giving us 3 buildings/floors, 8 rooms, and 32 tables to work with visually immediately.

### Phase 2: Building the Backend Backbone
**Step 4. Creating the Secure PDO Connection (`db.php`)**
Coded a secure connection script using PHP Data Objects (PDO) to protect the MySQL database from SQL injection attacks using Native Prepared Statements.

**Step 5. Establishing the REST API (`api.php`)**
Built a central router file designed to catch all web requests (via POST) and return strictly formatted JSON data instead of entirely new HTML pages.

**Step 6. Implementing Auth & Admin Sessions**
Integrated standard Session variables (`$_SESSION['user_id']`) into the API. This securely authenticates the admin so they can access the hidden backend dashboard.

**Step 7. Engineering the Availability Engine**
Wrote complex SQL queries inside the API to calculate booking capacity dynamically. The logic checks reservations against time slots and tables, ensuring no double-booking occurs.

### Phase 3: The Frontend Experience
**Step 8. Refactoring Static HTML to PHP Server Pages**
Changed the main interface file from `index.html` to `index.php`, allowing the server to inject native backend security and prepare the layout before the user even sees it.

**Step 9. Designing the 3D Building Stack UI (CSS)**
Re-engineered the floor plan from a basic grid to an isometric 3D "Building Stack." We utilized modern CSS box-shadows, animations, and transformations so clicking a level zooms the user into that floor smoothly.

**Step 10. The Vanilla JavaScript Class Engine (`app.js`)**
Encapsulated all frontend logic heavily inside an ES6 Class. This organized variables (selected date, active table) securely out of the global window, preventing code overlap.

**Step 11. Coding the Stage Manager & Asynchronous Fetch**
Programmed `goToStage()` for smooth UI switching (Floors → Rooms → Tables) and utilized `async/await fetch()` protocols to silently request data from `api.php`.

**Step 12. Dynamic Grid Matrix & Real-time Polling**
Attached the database table coordinates to the frontend CSS grid, meaning tables paint themselves exactly where they physically belong. A 30-second polling loop was added to lock tables globally if another user makes a booking simultaneously.

### Phase 4: Administrative Tooling
**Step 13. Securing the Admin Dashboard**
Programmed isolated JS elements bound to the admin login. This gives staff quick-toggle abilities over table statuses (e.g., marking a broken table as "inactive") completely hidden from public users.

**Step 14. Email Communications (`mailer.php`)**
Developed modular HTML templates. The system natively leverages PHPMailer (connected via Gmail SMTP) to dispatch real, branded confirmation receipts to users upon successful booking.

**Step 15. Real-Time Waitlist Integration**
Completed the user loop by establishing an admin waitlist panel. Staff can view waitlisted parties and hit a single "Notify" button to instantly fire an automated email offering them a physical table mapping in real-time.

---

## Part 2: Technologies Used (How & Why)

### 1. XAMPP
* **What it is:** A free, local cross-platform web server solution stack package.
* **How we used it:** It ran Apache (to host our local website) and MariaDB (for our database) synchronously on port 80 and 3306.
* **Why:** Developing a PHP application requires a server that can compile the code before sending it to the browser. XAMPP simulates a professional remote web server directly on a laptop, allowing instant feedback during development without paying for hosting.

### 2. PHP (Hypertext Preprocessor)
* **What it is:** A server-side scripting language perfectly suited for web development.
* **How we used it:** It functions as the strict "brain" of the application (`api.php` and `mailer.php`). It intercepts all user requests, talks to the MySQL database, processes emails, and securely returns data.
* **Why:** PHP is native, incredibly fast for simple REST APIs, and tightly coupled with MySQL. It natively processes HTTP inputs seamlessly.

### 3. MySQL (via phpMyAdmin)
* **What it is:** A relational database management system based on SQL.
* **How we used it:** We used `phpMyAdmin` (bundled in XAMPP) to visually import `schema.sql` and monitor live data injections.
* **Why:** We needed robust relationships (e.g., a specific table belongs to a specific room, which belongs to a specific floor). A relational database like MySQL ensures structural constraints perfectly enforce these real-world data rules.

### 4. SQL (Structured Query Language)
* **What it is:** The programming language used to communicate with the database.
* **How we used it:** To filter and manipulate data sets algorithmically. E.g., `SELECT table_id FROM reservations WHERE date = ? AND time_slot = ?` to verify real-time table availability.
* **Why:** It is significantly faster to have the SQL engine securely filter out unavailable tables than to pull all reservations into PHP and filter them using arrays.

### 5. Vanilla HTML
* **What it is:** HyperText Markup Language, the barebone structure of the visual web.
* **How we used it:** We defined the hidden modal overlays, the semantic structural containers for the building stack, and the interactive forms.
* **Why:** Keeping the HTML semantic and lean (without heavy templating languages) allows the JavaScript to easily find and manipulate the "skeleton" of the page.

### 6. Modern CSS (Cascading Style Sheets)
* **What it is:** A stylistic sheet language used for describing the presentation of HTML.
* **How we used it:** Implemented Flexbox, CSS Grid matrices, and `@keyframes` transformations. We designed glowing pseudo-elements for the table statuses natively.
* **Why:** Heavily leaning on modern CSS animations (`scale`, `translate`, `box-shadow`) provides a rich physical feeling (the building zooming effect) on the client side at 60 FPS without computationally expensive Javascript animation libraries.

### 7. Vanilla JavaScript (ES6)
* **What it is:** The programming language that runs locally inside the user's browser.
* **How we used it:** Acted as the interactive controller (`app.js`). It actively listens to mouse clicks, pulls data asynchronously using the `Fetch API`, and logically repaints the HTML structure on the fly cleanly.
* **Why:** By avoiding heavy frameworks like React or jQuery, the project maintains a tiny initial load footprint and relies on native, exceptionally fast browser features. This proves an advanced dynamic Single-Page Application (SPA) can be built utilizing raw fundamental web standards.
