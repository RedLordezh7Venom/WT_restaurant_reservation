# Development Journey: Restaurant Reservation System

This document is a step-by-step developer log. It simulates exactly how I built this fully functional, database-driven Restuarant Reservation System from 0 to 100%.

---

## Part 1: The 15-Step Build Log

### Phase 1: Environment & Database Setup
**Step 1. Setting Up the Local Server**
I started by installing XAMPP. This gave me an instant local Apache web server and a MySQL database without needing to buy cloud hosting. I created my project folder directly inside the `htdocs` directory so the code would execute locally.

**Step 2. Designing the MySQL Database**
Before writing any PHP, I opened phpMyAdmin and designed a relational database (`restaurant_db`). I mapped out exactly how the physical restaurant is laid out by separating the data into `floors`, `rooms`, and `tables`. 

**Step 3. Seeding Initial Data (`schema.sql`)**
To avoid manually typing tables every time I restarted the app, I wrote a `schema.sql` file. Running this file instantly populated my database with 3 building floors, 8 rooms, and 32 tables, giving me physical data to work with.

### Phase 2: Connecting the Backend (PHP)
**Step 4. Writing the Database Connection (`db.php`)**
I created `db.php` to hook my application into the MySQL database. I explicitly used PHP Data Objects (PDO) instead of old `mysqli` functions strictly because PDO completely protects the app from SQL Injection attacks using prepared statements.

**Step 5. Creating the Central API (`api.php`)**
Instead of having a dozen messy PHP files returning completely new web pages, I created a single `api.php` file mapping to a `switch` statement. It catches incoming requests, fetches JSON data from the database, and returns it dynamically to the frontend.

**Step 6. Developing the Availability Engine**
Inside the API, I wrote the core booking algorithm. I wrote SQL queries that check the `reservations` table and cross-reference it against my `tables` table, calculating accurately if a table is free at a requested time and date.

**Step 7. Securing the Administrator Session**
I didn't want just anyone editing tables. I injected PHP `$_SESSION` variables into the API so that passing the hardcoded `admin@restaurant.com` credentials unlocked a secure server-side session, granting access to hidden toolsets.

### Phase 3: Bringing the Frontend to Life
**Step 8. Building the Base Layout (`index.php`)**
I coded the semantic HTML structure, converting it into a `.php` file. This let me natively inject the backend admin session checks before the HTML even finishes rendering to the browser.

**Step 9. Styling the 3D Building Stack (CSS)**
I wanted the UI to feel incredibly premium. Using pure CSS, I arranged the floors as an isometric "Building Stack." I added box-shadow depths, parapet borders, and CSS `@keyframes` that smoothly zoom into a specific floor block when clicked.

**Step 10. Wiring the JavaScript Engine (`app.js`)**
I built an ES6 JavaScript Class to handle all the complex states. By wrapping everything in a Class, it securely tracked exactly which floor, room, and table the user was currently interacting with.

**Step 11. Fetching Data Asynchronously**
Through my JS class, I implemented the `fetch()` API. Every time you click a floor, JS silently asks `api.php` "Which tables are available in this room?", and instantly repaints the screen without refreshing the browser window.

**Step 12. Real-Time Auto-Refresh Polling**
To prevent double-bookings, I programmed a `setInterval` loop in JS. Every 30 seconds, it silently calls the server and instantly locks a table out (turning it red) if another customer sitting at a different computer books it.

### Phase 4: Finalizing Admin Tools & Email
**Step 13. Building the Hidden Admin Dashboard**
I constructed specific DOM toolbars that only render if the PHP session verifies an admin is logged in. This empowered the admin to flip tables offline manually or view an entire ledger of the day's schedules.

**Step 14. Setting Up PHPMailer & SMTP**
I realized the native `mail()` function in XAMPP does nothing locally. So, I imported the `PHPMailer` library explicitly and set it up to intercept completed bookings, routing a beautiful HTML email receipt out through Google's SMTP servers.

**Step 15. The Waitlist Manager**
Finally, I closed the loop. I created a persistent Waitlist panel. If the restaurant is full, a user joins the queue. When an admin sees a table frees up, they click a "Notify" button on their dashboard, which immediately triggers the PHP script to email the customer the good news. The project is 100% complete!

---

## Part 2: Technologies Used (How & Why)

### 1. XAMPP
* **What it is:** A free, local cross-platform web server solution stack package.
* **How I used it:** It ran Apache (to host my local website) and MariaDB (for my database) synchronously.
* **Why:** Developing a PHP application requires a server that can compile the code before sending it to the browser. XAMPP simulates a professional remote web server directly on a laptop, allowing instant feedback during development without paying for hosting.

### 2. PHP (Hypertext Preprocessor)
* **What it is:** A server-side scripting language perfectly suited for web development.
* **How I used it:** It functioned as the strict "brain" of the application (`api.php` and `mailer.php`). It intercepts all user requests, talks to the MySQL database, processes emails, and securely returns data.
* **Why:** PHP is native, incredibly fast for simple REST APIs, and tightly coupled with MySQL. It natively processes HTTP inputs seamlessly.

### 3. MySQL (via phpMyAdmin)
* **What it is:** A relational database management system based on SQL.
* **How I used it:** I used `phpMyAdmin` (bundled in XAMPP) to visually import `schema.sql` and monitor live data injections.
* **Why:** The system required robust relationships (e.g., a specific table belongs to a specific room, which belongs to a specific floor). A relational database like MySQL ensures structural constraints perfectly enforce these real-world data rules.

### 4. SQL (Structured Query Language)
* **What it is:** The programming language used to communicate with the database.
* **How I used it:** To filter and manipulate data sets algorithmically. E.g., `SELECT table_id FROM reservations WHERE date = ? AND time_slot = ?` to verify real-time table availability.
* **Why:** It is significantly faster to have the SQL engine securely filter out unavailable tables than to pull all reservations into PHP and filter them using arrays.

### 5. Vanilla HTML
* **What it is:** HyperText Markup Language, the barebone structure of the visual web.
* **How I used it:** I defined the hidden modal overlays, the semantic structural containers for the building stack, and the interactive forms.
* **Why:** Keeping the HTML semantic and lean (without heavy templating languages) allowed my JavaScript to easily find and manipulate the "skeleton" of the page.

### 6. Modern CSS (Cascading Style Sheets)
* **What it is:** A stylistic sheet language used for describing the presentation of HTML.
* **How I used it:** Implemented Flexbox, CSS Grid matrices, and `@keyframes` transformations. I designed glowing pseudo-elements for the table statuses natively.
* **Why:** Heavily leaning on modern CSS animations (`scale`, `translate`, `box-shadow`) provided a rich physical feeling (the building zooming effect) on the client side at 60 FPS without computationally expensive Javascript animation libraries.

### 7. Vanilla JavaScript (ES6)
* **What it is:** The dynamic programming language that runs locally inside the user's browser.
* **How I used it:** Acted as the interactive controller (`app.js`). It actively listens to mouse clicks, pulls data asynchronously using the `Fetch API`, and logically repaints the HTML structure on the fly cleanly.
* **Why:** By avoiding heavy frameworks like React or jQuery, the project maintains a tiny initial load footprint and relies on native, exceptionally fast browser features. This proved a highly advanced Single-Page Application (SPA) can be built utilizing raw fundamental web standards.
