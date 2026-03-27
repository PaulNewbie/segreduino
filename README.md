# Segreduino — Smart Waste Management System

Segreduino is a web-based smart waste management and segregation system integrated with IoT hardware. It provides a centralized dashboard to monitor trash bins, manage kiosks and machines, track tasks, and handle waste collection schedules — all communicating with ESP32 microcontrollers and an Arduino Mega as the primary hardware component.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Directory Structure](#directory-structure)
- [Troubleshooting](#troubleshooting)

---

## Features

- **IoT Integration** — Interfaces with ESP32 microcontrollers for real-time hardware monitoring, with Arduino Mega as the main controller.
- **Real-time Dashboard** — Monitor the status of bins, machines, and kiosks from a single view.
- **Task and Schedule Management** — Create, edit, and complete daily waste management tasks and collection schedules.
- **User Authentication** — Secure login, registration, password reset via PHPMailer, and Facebook Login API integration.
- **User Roles and Profiles** — Manage user profiles, avatars, roles, and application settings.
- **Alerts and History** — Track system notifications and review historical waste data.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL |
| Email | PHPMailer |
| Auth | Facebook Login API |

---

## Prerequisites

You only need the following to run this project locally:

- [PHP](https://www.php.net/downloads) 7.4 or higher
- [MySQL](https://dev.mysql.com/downloads/) (or [XAMPP](https://www.apachefriends.org/) / [WAMP](https://www.wampserver.com/) which bundles both)
- [Git](https://git-scm.com/downloads) (optional, for cloning)

Verify PHP is installed and accessible from your terminal:

```bash
php -v
```

---

## Getting Started

### 1. Clone or Download the Repository

**Option A — Clone via Git:**

```bash
git clone https://github.com/PaulNewbie/segreduino.git
cd segreduino
```

**Option B — Download ZIP:**

Download the ZIP from the repository page, extract it, and open a terminal inside the project folder.

---

### 2. Set Up the Database

Open your MySQL manager (e.g., phpMyAdmin or MySQL Workbench) and create a new database:

```sql
CREATE DATABASE smart_waste_management;
```

Then import the provided SQL file. You can do this via the terminal:

```bash
mysql -u root -p smart_waste_management < db_backup/smart_waste_management.sql
```

Or manually through phpMyAdmin by selecting the database, going to **Import**, and choosing `db_backup/smart_waste_management.sql`.

---

### 3. Configure the Database Connection

Open `src/config/config.php` and update the credentials to match your local MySQL setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_waste_management');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password
```

---

### 4. Run the Application

From inside the project directory, start PHP's built-in development server:

```bash
php -S 0.0.0.0:8000 index.php
```

Then open your browser and navigate to:

```
http://localhost:8000
```

> **Note:** Using `0.0.0.0` binds the server to all available network interfaces. This means other devices on the same local network can access the app via your machine's IP address (e.g., `http://192.168.1.5:8000`), which is useful for testing IoT hardware communication without deploying to a remote server.

> **Alternative (XAMPP / WAMP):** If you prefer Apache, place the project folder inside `htdocs` (XAMPP) or `www` (WAMP), start Apache and MySQL from the control panel, and navigate to `http://localhost/segreduino`.

---

## Directory Structure

```
segreduino/
├── assets/                        # Frontend assets (CSS, images, JavaScript)
├── db_backup/
│   └── smart_waste_management.sql # Database schema and seed data
├── src/
│   ├── config/                    # Database and application configuration
│   ├── controllers/
│   │   ├── Actions/               # CRUD operation handlers
│   │   └── Api/                   # API endpoints for IoT and frontend
│   ├── utils/                     # Shared helper functions (e.g., password hashing)
│   ├── vendor/                    # Third-party libraries (PHPMailer)
│   └── views/
│       ├── auth/                  # Authentication pages
│       │   ├── login.php
│       │   ├── register.php
│       │   ├── logout.php
│       │   ├── forgot_password.php
│       │   ├── verify_code.php
│       │   └── admin_reset_password.php
│       ├── layouts/               # Reusable UI components
│       │   ├── header.php
│       │   ├── sidebar.php
│       │   └── footer.php
│       ├── Legal/                 # Legal and compliance pages
│       │   ├── privacy-policy.html
│       │   ├── terms.html
│       │   └── delete-data.html
│       └── pages/                 # Main dashboard views
│           ├── dashboard.php
│           ├── bin.php
│           ├── tasks.php
│           ├── schedules.php
│           ├── notifications.php
│           ├── history.php
│           ├── profile.php
│           ├── settings.php
│           └── user.php
├── .htaccess                      # Apache routing rules
├── index.php                      # Application entry point
├── esp32_test_log.txt             # IoT hardware communication logs
└── README.md
```

---

## Troubleshooting

**Port 8000 is already in use.**
Stop the process occupying that port, or start the server on a different one:

```bash
php -S 0.0.0.0:8080 index.php
```

**Database connection fails on startup.**
Double-check the credentials in `src/config/config.php`. Ensure MySQL is running and the database name matches what was created in Step 2.

**Blank page or PHP errors are showing.**
Enable error reporting temporarily during development by adding the following to the top of `index.php`:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Remove or disable this before deploying to production.

**PHPMailer fails to send emails.**
Verify your SMTP credentials in the mail configuration. If using Gmail, use an [App Password](https://support.google.com/accounts/answer/185833) rather than your account password, and ensure 2FA with App Passwords is properly configured.

**Facebook Login does not work locally.**
Your Facebook Developer App must have `localhost` or your local IP listed as a valid OAuth redirect URI. Update this in the [Facebook Developer Console](https://developers.facebook.com/) under your app's Facebook Login settings.

**ESP32 data is not appearing in the dashboard.**
Confirm that the API endpoint URL in the ESP32 firmware points to your machine's local IP address (e.g., `http://192.168.1.5:8000/src/controllers/Api/...`). The device and your machine must be on the same local network.