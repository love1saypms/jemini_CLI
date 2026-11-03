CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('teacher','admin') NOT NULL DEFAULT 'teacher',
  active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE rooms (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, location VARCHAR(100) NOT NULL DEFAULT '', active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE periods (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, period_no SMALLINT UNSIGNED NOT NULL UNIQUE, label VARCHAR(50) NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, CHECK (start_time < end_time)) ENGINE=InnoDB;
CREATE TABLE reservations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  period_id BIGINT UNSIGNED NOT NULL,
  reservation_date DATE NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  purpose VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservation_room FOREIGN KEY (room_id) REFERENCES rooms(id),
  CONSTRAINT fk_reservation_period FOREIGN KEY (period_id) REFERENCES periods(id),
  CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT uq_reservation_slot UNIQUE (room_id, reservation_date, period_id),
  INDEX ix_reservation_date (reservation_date)
) ENGINE=InnoDB;
CREATE TABLE reservation_audit (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  period_id BIGINT UNSIGNED NOT NULL,
  reservation_date DATE NOT NULL,
  reserved_user_id BIGINT UNSIGNED NOT NULL,
  actor_user_id BIGINT UNSIGNED NOT NULL,
  action ENUM('cancel','admin_delete') NOT NULL,
  purpose VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_audit_date (reservation_date)
) ENGINE=InnoDB;

-- Initial accounts: both password = password. Login requires an immediate password change.
INSERT INTO users (username, full_name, password_hash, role, must_change_password) VALUES
('admin', '예약 관리자', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin', 1),
('teacher1', '교사 1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'teacher', 1);
INSERT INTO rooms (name, location) VALUES ('과학실', '본관 2층'), ('컴퓨터실', '본관 3층'), ('음악실', '별관 1층');
INSERT INTO periods (period_no, label, start_time, end_time) VALUES (1, '1교시', '08:50:00', '09:35:00'), (2, '2교시', '09:45:00', '10:30:00'), (3, '3교시', '10:40:00', '11:25:00'), (4, '4교시', '11:35:00', '12:20:00'), (5, '5교시', '13:20:00', '14:05:00'), (6, '6교시', '14:15:00', '15:00:00'), (7, '7교시', '15:10:00', '15:55:00');

