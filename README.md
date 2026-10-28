# LibraryHub — Stage 1 starter

A local-first PHP + MySQL/MariaDB library management system using Raspberry `#5B3DF5` and Pale Sky `#E8DEFF`.

## Requirements

- PHP 8.1+ (PHP 8.2+ recommended), PDO MySQL extension
- MySQL 8+ or MariaDB
- XAMPP / Apache for local development

## Install on XAMPP (Windows)

1. Extract `libraryhub` into `C:\xampp\htdocs\libraryhub`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Edit `app/config.php` and set the database credentials.
5. Visit `http://localhost/libraryhub/public/setup.php` once to create the first administrator.
6. Sign in at `http://localhost/libraryhub/public/login.php`.
7. After creating the administrator, delete or rename `public/setup.php`.

## Included in Stage 1

- First-admin setup and session-based login/logout
- Dashboard counts
- Book catalogue with copy counts and unique accession numbers
- Public member registration/listing
- Issue and return workflow with due dates
- Entry/exit stock movement register
- Basic configurable fine rate and loan period in config
- Responsive layout, search, and palette
- Prepared SQL statements, password hashing, CSRF tokens, role guard

## Important limitations

This is a development starter, not yet a production-ready deployment. Before going live, add automated tests, email notifications, fine payment receipts/reconciliation, reservation queue, barcode label printing/scanning integration, approval workflows, audit-log coverage for every mutation, rate limiting, and backup/restore testing. Use HTTPS and environment-based secrets in production. Never expose `setup.php` after initial setup.

## Data model

`books` stores title-level metadata; `book_copies` stores each physical copy with a unique accession code and status. `loans` records circulation history. `stock_movements` records acquisition and exit events. A title may have many physical copies.

## Corrected package notes

- `app/layout.php` loads `app/config.php`, so `require_login()` is defined before pages call it.
- The layout uses the external stylesheet at `public/assets/style.css`; no CSS is embedded in `layout.php`.
- The malformed overdue fine expression in `public/loans.php` has been corrected.
- After creating the first admin account, delete or rename `public/setup.php`.
- PHP syntax was checked for all PHP files in this package.
