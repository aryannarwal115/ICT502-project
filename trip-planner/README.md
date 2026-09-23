# Trip Planner

A complete, production-style Trip Planner web application built with PHP 8, MySQL, and vanilla JavaScript — designed to run on XAMPP.

## Features

- **Guest access:** landing page, about page, browse publicly shared itineraries
- **User accounts:** registration, login/logout, secure sessions, profile management, password change
- **Trip management:** full CRUD for trips (name, dates, description, cover image, status, notes)
- **Destinations:** add/edit/delete destinations per trip, with date-range validation against the trip
- **Activities:** add/edit/delete activities linked to destinations, with date/time validation
- **Expenses:** track flights, transport, accommodation, activities, food, and other costs; automatic category and grand totals calculated server-side
- **Itinerary view:** a clean, printable day-by-day itinerary with full cost breakdown, plus a "Print / Save as PDF" button (uses the browser's native print-to-PDF)
- **Sharing:** generate a secure, random public link for any trip; owner can disable sharing at any time
- **Packing lists & trip-prep checklists:** per-trip checkable lists with quick-add presets and a progress bar
- **Expense splitting:** add travelers to a trip, assign who paid each expense and who it's split between, and see an automatic "who owes whom" settlement summary
- **Activity notes/comments:** a lightweight comment thread on each activity for trip notes and discussion
- **Trip documents:** upload and attach tickets, bookings, passport scans, etc. (PDF/JPG/PNG/WEBP/DOC/DOCX) to a trip
- **Duplicate trip:** clone an existing trip (destinations, activities, checklists) as a starting point for a new one
- **Trip templates:** quick-fill starting points (Beach Vacation, City Break, Backpacking, Family Holiday, Business Trip) when creating a trip
- **Dark mode:** toggle in the header, preference remembered per browser
- **Admin panel:** separate login, dashboard stats, user search/view/activate/deactivate, trip search/view/delete
- **Security:** PDO prepared statements everywhere, `password_hash`/`password_verify`, CSRF tokens on every state-changing form, output escaping, session-based authorization checks, secure random share tokens, file-upload validation

## Not included (would need external services/infrastructure)

A few commonly-requested features were **not** added because they need something beyond this self-contained PHP/MySQL app: live weather data, an interactive map (both need a paid/keyed API), multi-currency auto-conversion (needs a live exchange-rate API), real-time collaboration/invites with permissions, in-app notifications or email reminders (needs a mail/queue setup), and offline/PWA support. The database and code are structured so any of these could be added later if you can supply the relevant API key or infrastructure.

## Technology Stack

- PHP 8+ (procedural, no framework)
- MySQL 5.7+/8+ (InnoDB)
- Vanilla HTML5 / CSS3 / JavaScript (no frontend framework, no build step)
- Runs on XAMPP (Apache + MySQL + PHP)

## Requirements

- XAMPP (or any Apache + PHP 8+ + MySQL stack)
- PHP extensions: `pdo_mysql`, `fileinfo` (both enabled by default in XAMPP)

## Setup Instructions (XAMPP)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g. `C:\xampp\htdocs\trip-planner` (Windows) or `/Applications/XAMPP/htdocs/trip-planner` (Mac).
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Click "Import", choose `database.sql`, and click "Go"
   - This creates the `trip_planner` database, all tables, and demo data.
   - **Upgrading an existing install?** Instead of re-importing `database.sql`, run `migration_new_features.sql` once to add the new tables/columns (packing lists, travelers, comments, documents) without touching your existing data.
4. **Configure the database connection:**
   - Open `config/database.php`
   - Update `DB_USER` / `DB_PASS` if your MySQL root user has a password (XAMPP default is usually blank).
5. **Run the app:**
   - Visit `http://localhost/trip-planner/index.php`

## Default Demo Credentials

> ⚠️ These are **DEMO credentials for local development only**. Change or remove them before any real deployment.

| Role  | Email                      | Password   |
|-------|-----------------------------|------------|
| Admin | admin@tripplanner.local     | Admin@123  |
| User  | demo@tripplanner.local      | Demo@123   |

Admin panel: `http://localhost/trip-planner/admin/login.php`

## Folder Structure

```
trip-planner/
├── admin/                  Admin panel (login, dashboard, users, trips, settings)
├── assets/css/style.css    All styling
├── assets/js/app.js        Client-side interactivity & validation
├── config/database.php     PDO connection settings
├── includes/               Shared PHP: auth, admin-auth, csrf, functions, header/footer
├── uploads/                Uploaded trip cover images
├── database.sql            Full schema + sample data
├── index.php, about.php, login.php, register.php, logout.php
├── dashboard.php, trips.php
├── create-trip.php, edit-trip.php, delete-trip.php, trip-details.php
├── add-destination.php, edit-destination.php, delete-destination.php
├── add-activity.php, edit-activity.php, delete-activity.php
├── expenses.php, delete-expense.php
├── itinerary.php, shared-trip.php, toggle-share.php
├── profile.php
└── README.md
```

## Security Notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plaintext.
- All database queries use PDO prepared statements to prevent SQL injection.
- Every state-changing form (create/edit/delete/toggle) includes and validates a CSRF token.
- All user-supplied output is escaped with `htmlspecialchars()` before being rendered.
- Every trip/destination/activity/expense operation verifies the record belongs to the logged-in user before acting on it (no direct object reference vulnerabilities).
- Share links use a 32-byte (`random_bytes`) cryptographically secure token — never the internal database ID.
- Admin sessions (`$_SESSION['admin_id']`) are kept separate from user sessions (`$_SESSION['user_id']`).
- Uploaded cover images are validated by real MIME type (via `finfo`) and size, renamed to random filenames, and capped at 5MB.
- Database errors are logged server-side and never shown to the browser.

## Troubleshooting

- **"A server error occurred"** — check `config/database.php` credentials and that MySQL is running.
- **Blank page / 500 error** — check your PHP error log (XAMPP: `xampp/php/logs/php_error_log`).
- **CSS/JS not loading** — make sure you're accessing the app via `http://localhost/trip-planner/...` (not `file://`), since asset paths are root-relative (`/assets/...`).
- **Uploads not saving** — ensure the `uploads/` folder is writable by the web server.
- **"Invalid or expired form submission"** — your session may have expired; refresh the page and try again (CSRF tokens are tied to your session).

## Notes

- Currency is stored per-expense (default `USD`) — this is a single-currency-per-expense model, not automatic conversion.
- The admin account cannot be deactivated from the Users page (only regular user accounts can be toggled).
