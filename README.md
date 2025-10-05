# Home Management System — Backend

This folder contains the PHP backend for the Home Management System. It is implemented using plain PHP scripts, a small class layer, PDO for database access, and JWT for authentication. PHPMailer is included for email/OTP functionality.

Location: `backend/home-management-system-Backend`

## Contents

- `api/` — public API endpoints (login, register, profiles, bookings, reviews, notifications, etc.)
- `class/` — PHP classes that contain business logic and model abstractions
- `database/setup_database.sql` — SQL script to create the required database and tables
- `api/db.php` — PDO database connector (default connection params included)
- `api/PHPMailer/` — PHPMailer library files (built-in wrapper and DSNConfigurator)
- `composer.json` — PHP dependencies (currently `firebase/php-jwt`)

## Requirements

- PHP 8.x with the following extensions installed:
  - pdo
  - pdo_mysql
  - mbstring
  - openssl
- MySQL or MariaDB
- Composer (https://getcomposer.org)
- Optional (for email): SMTP credentials for PHPMailer

## Installation

1. Open a terminal and navigate to the backend folder:

```powershell
cd .\backend\home-management-system-Backend
```

2. Install PHP dependencies with Composer:

```powershell
composer install
```

This will install `firebase/php-jwt` into the `vendor/` directory which the code expects.

## Database setup

1. Create the database and tables using the provided SQL script:

```powershell
# from project root or anywhere with MySQL client available
mysql -u root -p < .\backend\home-management-system-Backend\database\setup_database.sql
```

2. Default DB connection settings are in `api/db.php`:

- host: `localhost`
- db: `ServiceHub`
- user: `root`
- pass: `` (empty)

If your database uses different credentials or name, update `api/db.php` or set environment variables (recommended).

## Recommended: environment-based configuration

For better security and portability, replace hardcoded values in `api/db.php` with environment variables. Example modification:

```php
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'ServiceHub';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
```

You can then define these in your system environment or in an Apache virtual host, or use a small `.env` loader. Do not commit sensitive credentials to version control.

## Running the backend (development)

You can run the backend using the PHP built-in server for quick local testing:

```powershell
cd .\backend\home-management-system-Backend
php -S localhost:8000 -t api
```

This serves API files directly under `http://localhost:8000/` (for example, `http://localhost:8000/login.php`). For production, configure Apache/Nginx to serve `api/` and protect or hide implementation details.

## API Endpoints (overview)

The PHP files in `api/` act as endpoints. Important ones include:

- `register.php` — register a user (customer/provider)
- `login.php` — authenticate and return JWT
- `me.php` — returns the authenticated user profile (expects `Authorization: Bearer <token>`)
- `get_providers.php` — returns a list of providers
- `service_booking.php`, `subscription_booking.php` — booking-related endpoints
- `service_review.php`, `subscription_review.php` — review endpoints
- `provider_profile.php`, `customer_dashboard.php`, `provider_dashboard.php` — profile and dashboard endpoints

Refer to `documentation/api.md` for more details on request payloads and expected responses.

## Authentication & JWT

This project uses JWTs via `firebase/php-jwt`. Tokens are issued on login and should be included by the frontend using the `Authorization` header as follows:

```
Authorization: Bearer <token>
```

Check `documentation/jwt.md` for token format and validation details used in this project.

## Email / PHPMailer

PHPMailer is included in `api/PHPMailer/`. There is a lightweight wrapper included under `class/phpmailer.php` and a `DSNConfigurator.php` to help set SMTP credentials.

To send real emails (for OTP, password reset, notifications):

1. Provide SMTP credentials (host, username, password, port, encryption) - either directly in the configurator or via environment variables.
2. Test sending using the provided wrapper or a small script that calls the wrapper.

## Logging & error handling

- Enable display of errors in development by adjusting `php.ini` or by adding temporary error display lines at the top of `api` scripts:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

- In production, disable `display_errors` and log errors to a file instead.

## CORS (development)

If the frontend runs on a different origin (Vite dev server on `localhost:5173`) you may need to add the following headers to PHP endpoints during development:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

Adjust `Access-Control-Allow-Origin` to be more restrictive for production deployments.

## Troubleshooting

- Composer errors: run `composer diagnose` and confirm PHP version compatibility.
- Database connection issues: confirm MySQL service is running and credentials in `api/db.php` are correct.
- Endpoint 500 errors: check PHP error logs or temporarily enable display of errors (see Logging & error handling).
- Email sending issues: verify SMTP credentials and that your SMTP provider allows sending from local/dev environments.

## Development tips

- Keep API base URLs configurable in the frontend (use Vite env vars like `VITE_API_BASE_URL`).
- Add a `.env.example` with DB keys and `VITE_API_BASE_URL` to help teammates setup.
- Consider moving sensitive config to environment variables or a secure secrets manager before deploying.

## Where to find documentation

- API docs: `documentation/api.md`
- JWT notes: `documentation/jwt.md`
- OOP notes and other docs: `documentation/oop.md`, `documentation/valiSani.md`, etc.

---

If you want, I can also:

- Add a `backend/.env.example` file with example DB keys
- Edit `api/db.php` to read credentials from environment variables and provide a fallback
- Create a small `scripts/test_email.php` that attempts to send an email using the included PHPMailer wrapper (you'd still provide SMTP credentials)

Tell me which of those you'd like next and I'll implement it.

## Full-stack quickstart (if you're viewing only the backend)

If you cloned the repository and landed in the backend folder, follow these steps to run the complete application (backend + frontend):

1. Start the backend (in this folder):

```powershell
cd .\backend\home-management-system-Backend
composer install
php -S localhost:8000 -t api
```

2. Start the frontend in a new terminal (relative path from repository root):

```powershell
cd .\frontend\home-management-system-Frontend
npm install
npm run dev
```

3. Open the frontend at `http://localhost:5173`. If needed, create `frontend/home-management-system-Frontend/.env` with:

```
VITE_API_BASE_URL=http://localhost:8000
```

## Where the other repo/folder is

From the backend folder the frontend is located at: `..\..\frontend\home-management-system-Frontend`.
If you're browsing a single folder on GitHub, look for the sibling folder named `frontend/home-management-system-Frontend` in the parent repository to find the frontend code and quickstart steps.
