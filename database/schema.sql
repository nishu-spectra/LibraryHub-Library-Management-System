CREATE DATABASE IF NOT EXISTS libraryhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE libraryhub;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','librarian','inventory','auditor') NOT NULL DEFAULT 'librarian',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 author VARCHAR(190) NOT NULL,
 isbn VARCHAR(32) NULL,
 category VARCHAR(100) NULL,
 publisher VARCHAR(190) NULL,
 publication_year SMALLINT UNSIGNED NULL,
 shelf_location VARCHAR(80) NULL,
 description TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_books_title (title),
 INDEX idx_books_author (author),
 INDEX idx_books_isbn (isbn)
) ENGINE=InnoDB;

CREATE TABLE book_copies (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 book_id BIGINT UNSIGNED NOT NULL,
 accession_code VARCHAR(80) NOT NULL UNIQUE,
 barcode VARCHAR(100) NULL UNIQUE,
 status ENUM('available','issued','reserved','repair','lost','damaged','withdrawn') NOT NULL DEFAULT 'available',
 condition_note VARCHAR(255) NULL,
 acquired_at DATE NULL,
 acquisition_cost DECIMAL(10,2) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_copies_book FOREIGN KEY (book_id) REFERENCES books(id),
 INDEX idx_copies_status (status)
) ENGINE=InnoDB;

CREATE TABLE members (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 member_code VARCHAR(40) NOT NULL UNIQUE,
 full_name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NULL,
 phone VARCHAR(40) NULL,
 address TEXT NULL,
 member_type VARCHAR(60) NOT NULL DEFAULT 'Public',
 joined_on DATE NOT NULL,
 valid_until DATE NULL,
 status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_members_name (full_name),
 INDEX idx_members_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE loans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 copy_id BIGINT UNSIGNED NOT NULL,
 member_id BIGINT UNSIGNED NOT NULL,
 issued_by BIGINT UNSIGNED NOT NULL,
 returned_by BIGINT UNSIGNED NULL,
 issued_at DATETIME NOT NULL,
 due_at DATE NOT NULL,
 returned_at DATETIME NULL,
 renewals TINYINT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('issued','returned','overdue') NOT NULL DEFAULT 'issued',
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_loans_copy FOREIGN KEY (copy_id) REFERENCES book_copies(id),
 CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id),
 CONSTRAINT fk_loans_issuer FOREIGN KEY (issued_by) REFERENCES users(id),
 CONSTRAINT fk_loans_returner FOREIGN KEY (returned_by) REFERENCES users(id),
 INDEX idx_loans_status_due (status,due_at),
 INDEX idx_loans_member (member_id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 copy_id BIGINT UNSIGNED NOT NULL,
 movement_type ENUM('entry','transfer_in','transfer_out','disposal','loss','damage','repair','adjustment') NOT NULL,
 movement_date DATETIME NOT NULL,
 source_destination VARCHAR(190) NULL,
 reason VARCHAR(255) NOT NULL,
 reference_no VARCHAR(80) NULL,
 handled_by BIGINT UNSIGNED NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_movement_copy FOREIGN KEY (copy_id) REFERENCES book_copies(id),
 CONSTRAINT fk_movement_user FOREIGN KEY (handled_by) REFERENCES users(id),
 INDEX idx_movement_date (movement_date),
 INDEX idx_movement_type (movement_type)
) ENGINE=InnoDB;

CREATE TABLE fines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 waived_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 status ENUM('unpaid','partial','paid','waived') NOT NULL DEFAULT 'unpaid',
 assessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 notes VARCHAR(255) NULL,
 CONSTRAINT fk_fines_loan FOREIGN KEY (loan_id) REFERENCES loans(id),
 UNIQUE KEY uq_fine_loan (loan_id)
) ENGINE=InnoDB;

CREATE TABLE reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 book_id BIGINT UNSIGNED NOT NULL,
 member_id BIGINT UNSIGNED NOT NULL,
 requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 status ENUM('waiting','fulfilled','cancelled','expired') NOT NULL DEFAULT 'waiting',
 expires_at DATETIME NULL,
 CONSTRAINT fk_res_book FOREIGN KEY (book_id) REFERENCES books(id),
 CONSTRAINT fk_res_member FOREIGN KEY (member_id) REFERENCES members(id),
 INDEX idx_res_queue (book_id,status,requested_at)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 contact_name VARCHAR(120) NULL,
 email VARCHAR(190) NULL,
 phone VARCHAR(40) NULL,
 address TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE acquisitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 copy_id BIGINT UNSIGNED NOT NULL,
 source_type ENUM('purchase','donation','transfer','other') NOT NULL,
 supplier_id BIGINT UNSIGNED NULL,
 invoice_no VARCHAR(100) NULL,
 acquired_on DATE NOT NULL,
 cost DECIMAL(10,2) NULL,
 handled_by BIGINT UNSIGNED NOT NULL,
 notes TEXT NULL,
 CONSTRAINT fk_acq_copy FOREIGN KEY (copy_id) REFERENCES book_copies(id),
 CONSTRAINT fk_acq_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
 CONSTRAINT fk_acq_user FOREIGN KEY (handled_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(80) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 details JSON NULL,
 ip_address VARCHAR(45) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
 INDEX idx_audit_created (created_at),
 INDEX idx_audit_entity (entity_type,entity_id)
) ENGINE=InnoDB;
