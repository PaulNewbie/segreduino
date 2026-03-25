# Segreduino - Smart Waste Management System

Segreduino is a web-based smart waste management and segregation system integrated with IoT. It provides a centralized dashboard to monitor trash bins, manage kiosks/machines, track tasks, and handle waste collection schedules.

## 🚀 Features

* **IoT Integration:** Interfaces with ESP32 microcontrollers for hardware monitoring and Arduino Mega as main component.
* **Real-time Dashboard:** Monitor the status of bins, machines, and kiosks.
* **Task & Schedule Management:** Add, edit, and mark daily waste management tasks and schedules as done.
* **User Authentication:** Secure login, registration, password resets (via PHPMailer), and Facebook Login API integration.
* **User Roles & Profiles:** Manage user profiles, avatars, and settings.
* **Alerts & History:** Track system notifications and historical waste data.

## 🛠️ Tech Stack

* **Backend:** PHP
* **Frontend:** HTML, CSS, JavaScript
* **Database:** MySQL
* **Third-Party Integrations:** PHPMailer (Email Verification/Reset), Facebook Login API

## 📂 Directory Structure

```text
segreduino/
├── assets/                 # Frontend assets (CSS, Images, JS)
├── db_backup/              # Database schema and backup files
│   └── smart_waste_management.sql
├── src/                    # Main application source code
│   ├── config/             # Database and application configuration files
│   ├── controllers/        # Backend logic (Actions/ for CRUD, Api/ for endpoints)
│   ├── utils/              # Helper functions (e.g., password hashing)
│   ├── vendor/             # Third-party libraries (PHPMailer)
│   └── views/              # UI components and pages
│       ├── auth/           # Authentication interfaces
│       │   ├── admin_reset_password.php
│       │   ├── forgot_password.php
│       │   ├── login.php
│       │   ├── logout.php
│       │   ├── register.php
│       │   └── verify_code.php
│       ├── layouts/        # Reusable UI parts
│       │   ├── footer.php
│       │   ├── header.php
│       │   └── sidebar.php
│       ├── Legal/          # Legal and compliance pages
│       │   ├── delete-data.html
│       │   ├── privacy-policy.html
│       │   └── terms.html
│       └── pages/          # Main dashboard views
│           ├── bin.php
│           ├── dashboard.php
│           ├── history.php
│           ├── notifications.php
│           ├── profile.php
│           ├── schedules.php
│           ├── settings.php
│           ├── tasks.php
│           └── user.php
├── .htaccess               # Apache configuration / routing rules
├── index.php               # Application entry point
├── esp32_test_log.txt      # IoT hardware communication logs
└── README.md               # Project documentation


⚙️ Installation & Setup

    Clone or Download the Repository:
    Place the project folder into your local web server directory (e.g., htdocs for XAMPP or www for WAMP).

    Database Setup:

        Open your MySQL database manager (e.g., phpMyAdmin).

        Create a new database (e.g., smart_waste_management).

        Import the provided SQL file located at db_backup/smart_waste_management.sql.

    Configure Environment:

        Navigate to src/config/config.php.

        Update the database connection credentials (host, dbname, username, password) to match your local setup.

    Run the Application:

        Start your Apache and MySQL servers.

        Open your browser and navigate to http://localhost/segreduino (or your corresponding local path).