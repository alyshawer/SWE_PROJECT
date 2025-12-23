# Freelancer Job Board — Project & Test Documentation

## Overview
This is a clean, modern freelancer job board platform with full admin management, secure authentication, job posting, payments, and audit logging. The codebase is fully unit tested with PHPUnit and supports automated CI/CD.

## Project Structure
```
SWE_PROJECT/
├── app/
│   ├── controllers/      # MVC controllers (Admin, Auth, Job, Payment, etc.)
│   ├── models/           # Data models
│   ├── views/            # Application views (HTML/PHP)
│   ├── helpers/          # Helper functions (validation, DB, payments)
│   └── core/             # Core classes (BaseController, Database)
├── public/
│   ├── index.php         # Main entry point (router)
│   └── css/              # Stylesheets
├── tests/                # PHPUnit test suite
├── setup.sql             # Database schema
├── migration.sql         # Migration script
├── composer.json         # Composer dependencies
├── phpunit.xml           # PHPUnit config
└── README.md             # This file
```

## Features
- User registration (freelancer/client), secure login, session management
- Admin panel: manage users, jobs, payments, audit logs
- Job posting, application, and lifecycle management
- Payment creation and completion (with platform fee)
- Audit logging for all critical actions
- Responsive UI (HTML5/CSS3)
- Full test coverage with PHPUnit

## Setup Instructions
1. **Start XAMPP** (Apache & MySQL)
2. **Create database**: Use phpMyAdmin, import `setup.sql`.
3. **Install dependencies**:
   ```powershell
   & 'C:\xampp\php\php.exe' 'C:\xampp\php\composer.phar' install
   ```
4. **Run locally**: Visit http://localhost/SWE_PROJECT

## Running Tests
1. **Run all tests**:
   ```powershell
   composer test
   # or
   & "C:\xampp\php\php.exe" "vendor\bin\phpunit" --testdox --colors=always
   ```
2. **Test output**: All tests should pass (13 tests, 48 assertions). See below for details.

## Test Coverage — What’s Tested

### `tests/AdminControllerTest.php`
- Add user: verifies DB insert and redirect
- Delete user: checks is_deletable flag and DB removal
- Delete job: ensures job and related applications are deleted

### `tests/AuthControllerTest.php`
- User registration: verifies user creation, redirect, and client profile
- User login: checks session variables and redirect on success
- Login failure: ensures error message is returned for invalid credentials

### `tests/DatabaseTest.php`
- Database singleton: verifies setInstanceForTesting and getConnection

### `tests/DbFunctionsTest.php`
- Validation helpers: validateEmail, validatePassword, validateUsername, etc.
- User helpers: insertUser, checkUser (password verify)

### `tests/FreelancerModelTest.php`
- Creating freelancer profile and verifying getters/fields

### `tests/PaymentControllerTest.php`
- Creating a payment: verifies DB insert and validation
- Completing a payment: checks status update and freelancer earnings
- Validation for missing PayPal account

## Example Test Output
```
Admin Controller
 ✔ Add user creates user
 ✔ Delete user respects deletable flag
 ✔ Delete job deletes and cascades

Auth Controller
 ✔ Register creates user
 ✔ Login authenticates user
 ✔ Login fails with invalid credentials

Database
 ✔ Set instance for testing

Db Functions
 ✔ Validation helpers
 ✔ Insert and check user

Freelancer Model
 ✔ Create and getters

Payment Controller
 ✔ Create payment as client succeeds
 ✔ Create payment missing account fails for paypal
 ✔ Complete payment by client succeeds and updates earnings

OK (13 tests, 48 assertions)
```

## How to Export Test Output for Documentation
1. Run:
   ```powershell
   & "C:\xampp\php\php.exe" "vendor\bin\phpunit" --testdox > test-report.txt
   ```
2. Convert to HTML:
   - Use any Markdown/HTML editor, or online converter (paste the output).
   - Or run:
     ```powershell
     & "C:\xampp\php\php.exe" "vendor\bin\phpunit" --testdox-html test-report.html
     ```
3. Convert HTML to PDF:
   - Open `test-report.html` in browser, print to PDF.

## CI/CD & Remote Testing
- Tests run in CI (GitHub Actions) and can be run in Docker or any remote server with PHP/Composer.
- SQLite in-memory used for fast, isolated unit tests; integration tests can be added for MariaDB/MySQL.

## Contact & Contribution
- For issues or contributions, open a GitHub issue or PR.

---
**Project maintained by [Your Name/Team].**

The platform is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

Clean, simple, and professional design for easy use.