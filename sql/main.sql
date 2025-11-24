-- Create database
CREATE DATABASE bank_simulator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bank_simulator;

-- TABLE: users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    email TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLE: profile
DROP TABLE IF EXISTS profile;
CREATE TABLE profile (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(50) NOT NULL,
    DOB DATE NOT NULL,
    phone TEXT NOT NULL,
    Address TEXT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- TABLE: account
DROP TABLE IF EXISTS account;
CREATE TABLE account (
    account_id BIGINT NOT NULL AUTO_INCREMENT,
    profile_id BIGINT NOT NULL,
    account_type ENUM('savings', 'current', 'salary') NOT NULL,
    account_number BIGINT NOT NULL,
    balance BIGINT NOT NULL,
    min_balance BIGINT DEFAULT NULL,
    status ENUM('Active', 'Pending') NOT NULL,
    ifsc_code VARCHAR(20) NOT NULL DEFAULT 'INDB0000323',
    account_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (account_id),
    FOREIGN KEY (profile_id) REFERENCES profile(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Transaction table
DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction` (
    id INT NOT NULL AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_type ENUM('deposit','withdraw','transfer') NOT NULL,
    amount BIGINT NOT NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    performed_by INT NOT NULL,
    status ENUM('completed','pending','cancelled') NOT NULL DEFAULT 'completed',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Users table Data
INSERT INTO users (id, username, role, email, created_at, last_login, password_hash) VALUES
(1, 'Ankit', 'admin', 'ankitrawat@gmail.com', '2025-11-18 07:01:00', '2025-11-18 07:01:00', '$2y$12$dy8UcRaiczzu8NgHQjaQh.icZKuNdkLt0/40Ji0ubH9OaVDmwlRqy'),
(2, 'Ajay01', 'customer', 'ajay@gmail.com', '2025-11-18 11:22:26', '2025-11-18 11:22:26', '$2y$12$/1/1mDHUhBVfPGPQC4yWUe.GQM3wTZ5ypvhsYtRYXGhqb9PWqDeCW'),
(4, 'aman01', 'customer', 'aman@gmail.com', '2025-11-18 05:58:15', '2025-11-18 05:58:15', '$2y$12$LpPBASFmagrncoReoQL0tekGz25PvSiIVIpm2S7bM7mrd9Kiya0f.'),
(5, 'sumit', 'customer', 'sumitgarg@gmail.com', '2025-11-19 04:00:00', '2025-11-19 04:00:00', '$2y$12$rpOV5313faN0BbfSu3IaLOnttcOpUvQKcEIipOoqbbSKkLnn5Or4W'),
(6, 'chandan', 'customer', 'chandan@gmail.com', '2025-11-20 12:00:00', '2025-11-20 12:00:00', '$2y$12$81uphfxKzF8iGuEUvNSjduXNSFD/uLtNXK4SgYFOt.ALDNpuVDUPy'),
(7, 'viany', 'customer', 'vinay@gmail.com', '2025-11-21 05:02:43', '2025-11-21 05:02:43', '$2y$12$u/CN0Zr5U1h8lJB7UoMIT.Ak/ulL00cbZkMzJeuKARFFUtqHQHtD2'),
(8, 'kashish', 'customer', 'kashish@gmail.com', '2025-11-21 06:20:00', '2025-11-21 06:20:00', '$2y$12$g5tuFOy3Girl0FTzUELAKebplUpN74wFTUM/FFG16b2FG0cGfLXjC'),
(9, 'Mohit', 'customer', 'mohit@gmail.com', '2025-11-21 09:03:37', '2025-11-21 09:03:37', '$2y$12$IhV6cNmc6UyQsdDzLyOvQ.keeHDrZ69wYVhCkXlJ5BL0uO5qQWxZu');


--  Profile table Data
INSERT INTO profile (id, user_id, full_name, DOB, phone, Address) VALUES
(1, 5, 'Sumit Garg', '2000-01-10', '9564823654', '101, bilaspur, india'),
(2, 4, 'Aman Rawat', '1999-02-02', '7906722965', '105,New Delhi, India'),
(3, 2, 'Ajay Roy', '2001-03-06', '9756248915', '502, New Delhi, India'),
(4, 6, 'chandan', '2003-02-11', '9756248915', '11,haryana'),
(5, 7, 'Vinay', '2002-07-25', '8569456820', '203, Haryana, India'),
(6, 8, 'Kashish Mittal', '2004-03-21', '7596458123', '101,hansi,India'),
(7, 9, 'Mohit Kumar', '2002-03-07', '7546952365', '101, Chandigarh, India');


--  Account table Data
INSERT INTO account (account_id, profile_id, account_type, account_number, balance, min_balance, status, ifsc_code, account_date) VALUES
(1, 1, 'savings', 5510894543, 190, NULL, 'Active', 'INDB0000323', '0000-00-00 00:00:00'),
(3, 1, 'savings', 1604531907, 1000, 1000, 'Active', 'INDB0000323', '0000-00-00 00:00:00'),
(8, 4, 'current', 5040695771, 1000, 1000, 'Active', 'INDB0000323', '0000-00-00 00:00:00'),
(9, 4, 'savings', 1382350014, 1500, 1500, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(10, 5, 'savings', 2505450134, 10, 10, 'Active', 'INDB0000323', '2025-11-18 11:28:15'),
(11, 2, 'savings', 1999456111, 0, NULL, 'Active', 'INDB0000323', '2025-11-19 09:37:53'),
(12, 3, 'savings', 6073305580, 30, 10, 'Active', 'INDB0000323', '2025-11-21 11:53:21'),
(15, 6, 'salary', 2915132205, 2000, 2000, 'Active', 'INDB0000323', '2025-11-21 11:53:21'),
(16, 7, 'salary', 9198726596, 2000, 2000, 'Active', 'INDB0000323', '2025-11-21 14:33:37');


INSERT INTO `transaction` 
(id, account_id, transaction_type, amount, transaction_date, performed_by, status) VALUES
(1, 3, 'deposit', 500, '2025-11-24 11:40:53', 1, 'completed'),
(2, 3, 'deposit', 500, '2025-11-24 11:41:10', 1, 'completed'),
(3, 3, 'withdraw', 100, '2025-11-24 11:41:47', 1, 'completed'),
(4, 3, 'transfer', 100, '2025-11-24 14:38:11', 1, 'completed'),
(5, 11, 'transfer', 100, '2025-11-24 14:38:11', 1, 'completed');
