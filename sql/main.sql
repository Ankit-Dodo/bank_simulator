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
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
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
    id INT NOT NULL AUTO_INCREMENT,
    profile_id INT NOT NULL,
    account_type ENUM('savings', 'current', 'salary') NOT NULL,
    account_number bigint NOT NULL UNIQUE,
    balance INT NOT NULL,
    min_balance int DEFAULT NULL,
    status ENUM('Active', 'Pending') NOT NULL,
    ifsc_code VARCHAR(20) NOT NULL DEFAULT 'INDB0000323',
    account_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (profile_id) REFERENCES profile(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Transaction table
DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction` (
    id INT NOT NULL AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_type ENUM('deposit','withdraw','transfer') NOT NULL,
    amount int NOT NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    performed_by INT NOT NULL,
    status ENUM('completed','pending','cancelled') NOT NULL DEFAULT 'completed',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Users table Data
INSERT INTO `users`
(id, username, role, email, created_at, last_login, password_hash, status) VALUES
(1, 'Ankit', 'admin', 'ankitrawat@gmail.com', '2025-11-18 12:31:04', '2025-11-20 18:28:56', '$2y$12$dy8UcRaiczzu8NgHQjaQh.icZKuNdkLt0/40Ji0ubH9OaVDmwlRqy', 'Active'),
(2, 'Ajay01', 'customer', 'ajay@gmail.com', '2025-11-18 16:52:26', '2025-11-20 17:26:43', '$2y$12$/1/1mDHUhBVfPGPQC4yWUe.GQM3wTZ5ypvhsYtRYXGhqb9PWqDeCW', 'Active'),
(4, 'aman01', 'customer', 'aman@gmail.com', '2025-11-18 11:28:15', '2025-11-20 17:50:15', '$2y$12$LpPBASFmagrncoReoQL0tekGz25PvSiIVIpm2S7bM7mrd9Kiya0f.', 'Active'),
(5, 'sumit', 'customer', 'sumitgarg@gmail.com', '2025-11-19 09:37:53', '2025-11-20 14:36:44', '$2y$12$rpOV5313faN0BbfSu3IaLOnttcOpUvQKcEIipOoqbbSKkLnn5Or4W', 'Active'),
(6, 'chandan', 'customer', 'chandan@gmail.com', '2025-11-20 17:38:37', '2025-11-20 18:18:40', '$2y$12$81uphfxKzF8iGuEUvNSjduXNSFD/uLtNXK4SgYFOt.ALDNpuVDUPy', 'Active'),
(7, 'viany', 'customer', 'vinay@gmail.com', '2025-11-21 10:32:43', '2025-11-21 10:32:43', '$2y$12$u/CN0Zr5U1h8lJB7UoMIT.Ak/ulL00cbZkMzJeuKARFFUtqHQHtD2', 'Active'),
(8, 'kashish', 'customer', 'kashish@gmail.com', '2025-11-21 11:53:21', '2025-11-21 11:53:21', '$2y$12$g5tuFOy3Girl0FTzUELAKebplUpN74wFTUM/FFG16b2FG0cGfLXjC', 'Active'),
(9, 'Mohit', 'customer', 'mohit@gmail.com', '2025-11-21 14:33:37', '2025-11-28 16:20:31', '$2y$12$IhV6cNmc6UyQsdDzLyOvQ.keeHDrZ69wYVhCkXlJ5BL0uO5qQWxZu', 'Active'),
(10, 'Garima', 'customer', 'garima@gmail.com', '2025-11-21 15:26:33', '2025-11-21 15:26:33', '$2y$12$dS/AQR0872qzyDumJfoDHORhm9SmrRSolmrpzp7tBscX9WaFZ8z.', 'Active'),
(11, 'Vikrant', 'customer', 'vikrant@gmail.com', '2025-11-28 17:45:45', '2025-11-28 17:45:45', '$2y$12$d/TqChUI92clZf/H5TIDu.TvW8Kkvb/YOITBnXg1.LdjcmzQ9ciK2', 'Active');


--  Profile table Data
INSERT INTO `profile`
(id, user_id, full_name, DOB, phone, Address) VALUES
(1, 5, 'Sumit Garg', '2000-01-10', '9564823654', '101, bilaspur, india'),
(2, 4, 'Aman Rawat', '1999-02-02', '7906722965', '105,New Delhi, India'),
(3, 2, 'Ajay Roy', '2001-03-06', '8569456237', '502, New Delhi, india0'),
(4, 6, 'chandan', '2003-02-11', '9756248915', '11,haryana'),
(5, 7, 'Vinay', '2002-07-25', '8569456820', '203, Haryana, India'),
(6, 8, 'Kashish Mittal', '2004-03-21', '7596458123', '101,hansi,India'),
(7, 9, 'Mohit Kumar', '2002-03-07', '7546952365', '101, Chandigarh, India'),
(8, 10, 'Garima Tomar', '2003-08-13', '9562348567', '502, meerut, India'),
(9, 11, 'Vikrant Yadav', '2005-06-01', '9564875623', '203,meerut');




--  Account table Data
INSERT INTO account (id, profile_id, account_type, account_number, balance, min_balance, status, ifsc_code, account_date) VALUES
(1, 1, 'savings', '5510894543', 800, NULL, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(3, 2, 'savings', '1604531907', 1730, 1000, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(8, 4, 'current', '5040695771', 1000, 1000, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(9, 4, 'savings', '1382350014', 2000, 1500, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(10, 5, 'savings', '2505450134', 10800, 10, 'Active', 'INDB0000323', '2025-11-21 10:32:43'),
(11, 2, 'savings', '1999445611', 3690, NULL, 'Active', 'INDB0000323', '2025-11-18 11:28:15'),
(12, 1, 'savings', '6073305580', 700, 10, 'Active', 'INDB0000323', '2025-11-19 09:37:53'),
(13, 6, 'salary', '2915132205', 2000, 500, 'Active', 'INDB0000323', '2025-11-21 11:53:21'),
(15, 7, 'salary', '9198726596', 20000, 200, 'Active', 'INDB0000323', '2025-11-21 14:33:37'),
(16, 8, 'savings', '2834645532', 1000, 500, 'Active', 'INDB0000323', '2025-11-21 15:26:33'),
(17, 1, 'savings', '3599136868', 500, 500, 'Active', 'INDB0000323', '2025-11-19 09:37:53'),
(18, 3, 'savings', '3415149038', 5000, NULL, 'Active', 'INDB0000323', '2025-11-18 16:52:26'),
(19, 9, 'savings', '9263465112', 10000, NULL, 'Active', 'INDB0000323', '2025-11-28 17:45:45');




INSERT INTO transaction (id, account_id, transaction_type, amount, transaction_date, performed_by, status) VALUES
(1, 3, 'deposit', 500, '2025-11-24 11:40:53', 1, 'completed'),
(2, 3, 'deposit', 500, '2025-11-24 11:41:10', 1, 'completed'),
(3, 3, 'withdraw', 100, '2025-11-24 11:41:47', 1, 'completed'),
(4, 3, 'transfer', 100, '2025-11-24 14:38:11', 1, 'completed'),
(5, 11, 'transfer', 100, '2025-11-24 14:38:11', 1, 'completed'),
(6, 8, 'transfer', 400, '2025-11-25 09:28:40', 6, 'completed'),
(7, 9, 'transfer', 400, '2025-11-25 09:28:40', 6, 'completed'),
(8, 9, 'withdraw', 100, '2025-11-25 09:35:38', 6, 'completed'),
(9, 3, 'withdraw', 100, '2025-11-25 10:07:56', 1, 'completed'),
(10, 10, 'deposit', 500, '2025-11-25 10:25:31', 1, 'completed'),
(11, 3, 'transfer', 200, '2025-11-25 10:27:03', 1, 'completed'),
(12, 10, 'transfer', 200, '2025-11-25 10:27:03', 1, 'completed'),
(13, 10, 'deposit', 100, '2025-11-25 11:20:54', 1, 'completed'),
(14, 10, 'withdraw', 100, '2025-11-25 11:21:18', 1, 'completed'),
(15, 13, 'transfer', 150, '2025-11-25 11:24:55', 1, 'completed'),
(16, 1, 'transfer', 150, '2025-11-25 11:24:55', 1, 'completed'),
(17, 1, 'deposit', 120, '2025-11-25 17:14:49', 1, 'completed'),
(18, 11, 'withdraw', 100, '2025-11-28 09:48:55', 1, 'completed'),
(19, 16, 'deposit', 100, '2025-11-28 10:44:17', 1, 'completed'),
(20, 13, 'deposit', 500, '2025-11-28 11:04:56', 1, 'completed'),
(21, 13, 'withdraw', 100, '2025-11-28 11:09:20', 1, 'completed'),
(22, 13, 'withdraw', 10, '2025-11-28 11:10:42', 1, 'completed'),
(23, 3, 'transfer', 100, '2025-11-28 11:15:53', 1, 'completed'),
(24, 10, 'transfer', 100, '2025-11-28 11:15:53', 1, 'completed'),
(25, 13, 'withdraw', 10, '2025-11-28 12:42:54', 1, 'completed'),
(26, 3, 'withdraw', 100, '2025-11-28 12:45:10', 1, 'completed'),
(27, 11, 'withdraw', 100, '2025-11-28 12:53:40', 1, 'completed'),
(28, 11, 'withdraw', 1, '2025-11-28 12:59:15', 1, 'completed'),
(29, 3, 'withdraw', 1, '2025-11-28 13:02:19', 1, 'completed'),
(30, 3, 'withdraw', 1, '2025-11-28 13:04:42', 1, 'completed'),
(31, 3, 'withdraw', 2, '2025-11-28 13:04:56', 1, 'completed'),
(32, 3, 'withdraw', 6, '2025-11-28 14:46:04', 4, 'completed'),
(33, 3, 'withdraw', 10, '2025-11-28 14:46:43', 4, 'completed'),
(34, 3, 'withdraw', 10, '2025-11-28 14:55:29', 4, 'completed'),
(35, 3, 'withdraw', 10, '2025-11-28 14:55:59', 4, 'completed'),
(36, 3, 'transfer', 10, '2025-11-28 15:00:26', 4, 'completed'),
(37, 11, 'transfer', 10, '2025-11-28 15:00:26', 4, 'completed'),
(38, 11, 'withdraw', 9, '2025-11-28 15:00:46', 4, 'completed'),
(39, 11, 'deposit', 100, '2025-11-28 15:01:16', 1, 'completed'),
(40, 11, 'withdraw', 10, '2025-11-28 15:03:50', 1, 'completed'),
(41, 3, 'withdraw', 10, '2025-11-28 15:05:33', 1, 'completed'),
(42, 3, 'withdraw', 10, '2025-11-28 15:19:29', 1, 'completed'),
(43, 18, 'deposit', 5000, '2025-11-28 16:18:18', 1, 'completed'),
(44, 19, 'deposit', 10000, '2025-11-28 17:48:39', 1, 'completed'),
(45, 1, 'deposit', 5000, '2025-11-29 12:14:28', 1, 'completed'),
(46, 10, 'deposit', 5000, '2025-11-29 12:21:54', 1, 'completed'),
(47, 13, 'withdraw', 30, '2025-11-29 12:26:48', 1, 'completed'),
(48, 10, 'deposit', 5000, '2025-11-29 12:27:21', 1, 'completed'),
(49, 12, 'withdraw', 20, '2025-11-29 12:29:57', 5, 'completed'),
(50, 11, 'deposit', 2000, '2025-11-30 19:40:16', 1, 'completed'),
(51, 11, 'deposit', 500, '2025-11-30 19:42:34', 1, 'completed'),
(52, 11, 'deposit', 500, '2025-11-30 19:43:11', 1, 'completed'),
(53, 11, 'deposit', 200, '2025-11-30 19:43:48', 1, 'completed'),
(54, 12, 'deposit', 500, '2025-11-30 19:51:19', 1, 'completed'),
(55, 16, 'deposit', 400, '2025-11-30 19:59:56', 1, 'completed'),
(56, 10, 'withdraw', 10, '2025-11-30 20:16:59', 1, 'completed'),
(57, 12, 'withdraw', 20, '2025-11-30 20:27:04', 1, 'completed'),
(58, 13, 'transfer', 200, '2025-11-30 20:27:52', 1, 'completed'),
(59, 9, 'transfer', 200, '2025-11-30 20:27:52', 1, 'completed');

