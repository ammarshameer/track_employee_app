-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 06:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `emp_track_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `admin_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_username`, `admin_password`, `admin_email`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$6x5R2uNitI0pfTLzkOvbzOeR8MSZRlM4F1O4L.smhIQsj1IUDVsA6', 'admin@emptrack.com', '2025-11-16 08:36:01', '2025-11-16 08:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `login_image_path` varchar(255) DEFAULT NULL,
  `logout_image_path` varchar(255) DEFAULT NULL,
  `login_latitude` decimal(10,8) DEFAULT NULL,
  `login_longitude` decimal(11,8) DEFAULT NULL,
  `logout_latitude` decimal(10,8) DEFAULT NULL,
  `logout_longitude` decimal(11,8) DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `total_seconds` int(11) DEFAULT 0,
  `work_duration` time DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','partial') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `login_time`, `logout_time`, `login_image_path`, `logout_image_path`, `login_latitude`, `login_longitude`, `logout_latitude`, `logout_longitude`, `total_hours`, `total_seconds`, `work_duration`, `attendance_date`, `status`, `created_at`) VALUES
(1, 1, '2025-11-16 08:42:32', '2025-11-16 08:42:32', '../../uploads/login_images/2025/11/16/1_094134_69198e3e09abb.jpg', '../../uploads/logout_images/2025/11/16/1_094232_69198e78977cd.jpg', 37.42199830, -122.08400000, 37.42199833, -122.08400000, 0.00, 58, '00:00:58', '2025-11-16', 'present', '2025-11-16 08:41:34'),
(2, 3, '2025-11-17 04:57:11', '2025-11-17 04:57:11', '../../uploads/login_images/2025/11/17/3_053846_691aa6d6e6d77.jpg', '../../uploads/logout_images/2025/11/17/3_055711_691aab27b23f7.jpg', 37.42199830, -122.08400000, 37.42199830, -122.08400000, 0.30, 1105, '00:18:25', '2025-11-17', 'present', '2025-11-17 04:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `hire_date` date NOT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_number`, `first_name`, `last_name`, `email`, `phone`, `address`, `date_of_birth`, `hire_date`, `salary`, `department`, `position`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'John', 'Doe', 'john.doe@company.com', '1234567890', NULL, NULL, '2024-01-15', 50000.00, 'Field Operations', 'Field Worker', '$2y$10$ilTnXYuw4dQ2H6UbYlqbAuMMT.dIapEbr0wwzuExUSImC9byp5Sym', 1, '2025-11-16 08:36:01', '2025-11-16 08:36:01'),
(2, 'EMP002', 'Jane', 'Smith', 'jane.smith@company.com', '0987654321', NULL, NULL, '2024-02-01', 45000.00, 'Field Operations', 'Field Worker', '$2y$10$ilTnXYuw4dQ2H6UbYlqbAuMMT.dIapEbr0wwzuExUSImC9byp5Sym', 1, '2025-11-16 08:36:01', '2025-11-16 08:36:01'),
(3, 'EMP006', 'dummy', 'dummy', 'dummyuser@gmail.com', '+92364458784', '', NULL, '2025-11-17', 120500.00, 'Engineer', 'Civil', '$2y$10$HhwesPXg9VyOE9MaFXF7ue6cQuaCX2bW67tvmR7Dx5tQwbVYycZMe', 1, '2025-11-17 04:38:08', '2025-11-17 04:38:08');

-- --------------------------------------------------------

--
-- Table structure for table `employee_sessions`
--

CREATE TABLE `employee_sessions` (
  `session_id` varchar(255) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `device_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_sessions`
--

INSERT INTO `employee_sessions` (`session_id`, `employee_id`, `login_time`, `last_activity`, `is_active`, `device_info`) VALUES
('39125621a058022a1b6169742b824b7e3ae2278334df15fd78c709316f5b7651', 3, '2025-11-17 04:38:47', '2025-11-17 04:57:11', 0, 'Android Device'),
('8b546da63042590b94e5910b32eb6a31955527251c0a677bb0762b9bf6d1dc5c', 1, '2025-11-16 08:41:34', '2025-11-16 08:42:32', 0, 'Android Device');

-- --------------------------------------------------------

--
-- Table structure for table `gps_tracking`
--

CREATE TABLE `gps_tracking` (
  `tracking_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `speed` decimal(8,2) DEFAULT NULL,
  `altitude` decimal(8,2) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `tracking_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gps_tracking`
--

INSERT INTO `gps_tracking` (`tracking_id`, `employee_id`, `latitude`, `longitude`, `accuracy`, `speed`, `altitude`, `timestamp`, `tracking_date`) VALUES
(1, 1, 37.42199833, -122.08400000, 20.00, 0.00, 5.00, '2025-11-16 08:41:46', '2025-11-16'),
(2, 3, 37.42199833, -122.08400000, 20.00, 0.00, 5.00, '2025-11-17 04:38:59', '2025-11-17'),
(3, 3, 37.42199830, -122.08400000, 12.30, 0.00, 5.00, '2025-11-17 04:48:48', '2025-11-17');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `total_hours` decimal(8,2) DEFAULT 0.00,
  `hourly_rate` decimal(8,2) DEFAULT 0.00,
  `overtime_hours` decimal(8,2) DEFAULT 0.00,
  `overtime_rate` decimal(8,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_username` (`admin_username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_attendance_employee_date` (`employee_id`,`attendance_date`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `idx_employee_number` (`employee_number`);

--
-- Indexes for table `employee_sessions`
--
ALTER TABLE `employee_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_sessions_employee` (`employee_id`);

--
-- Indexes for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `idx_gps_employee_date` (`employee_id`,`tracking_date`),
  ADD KEY `idx_gps_timestamp` (`timestamp`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `idx_payroll_employee` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_sessions`
--
ALTER TABLE `employee_sessions`
  ADD CONSTRAINT `employee_sessions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD CONSTRAINT `gps_tracking_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
