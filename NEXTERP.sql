-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 23, 2025 at 04:22 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u587956043_NEXTERP`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) UNSIGNED NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'IT', 'Information Technology department.', '2025-09-21 20:52:00', '2025-09-21 20:52:00'),
(2, 'HR', 'Human Resources department.', '2025-09-21 20:52:00', '2025-09-21 20:52:00'),
(3, 'Finance', 'Finance and Accounting department.', '2025-09-21 20:52:00', '2025-09-21 20:52:00'),
(4, 'Sales', 'Sales and Marketing department.', '2025-09-21 20:52:00', '2025-09-21 20:52:00'),
(5, 'Legal', 'Legal and Compliance department.', '2025-09-21 20:52:00', '2025-09-21 20:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_number` varchar(50) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `full_name` varchar(512) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `department_id` int(11) UNSIGNED NOT NULL,
  `reporting_manager` int(11) UNSIGNED DEFAULT NULL,
  `expense_approver` int(11) UNSIGNED DEFAULT NULL,
  `date_of_joining` date NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_mobile` varchar(50) NOT NULL,
  `employee_status` enum('active','on_leave','resigned','terminated','probation') NOT NULL DEFAULT 'active',
  `address` text DEFAULT NULL,
  `country` varchar(2) DEFAULT 'PK',
  `citizenship` varchar(255) DEFAULT NULL,
  `identity_card_number` varchar(255) DEFAULT NULL,
  `tax_payer_id` varchar(255) DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `confirmed_salary` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PKR',
  `increment_percentage` decimal(5,2) DEFAULT 0.00,
  `increment_month` varchar(7) DEFAULT NULL,
  `overtime_rate_multiplier` decimal(5,2) DEFAULT 1.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_iban` varchar(255) DEFAULT NULL,
  `account_title` varchar(255) DEFAULT NULL,
  `branch_code` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `emergency_contact_address` text DEFAULT NULL,
  `dependents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dependents`)),
  `profile_photo_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_number`, `first_name`, `last_name`, `full_name`, `designation`, `department_id`, `reporting_manager`, `expense_approver`, `date_of_joining`, `date_of_birth`, `contact_email`, `contact_mobile`, `employee_status`, `address`, `country`, `citizenship`, `identity_card_number`, `tax_payer_id`, `basic_salary`, `confirmed_salary`, `currency`, `increment_percentage`, `increment_month`, `overtime_rate_multiplier`, `bank_name`, `bank_iban`, `account_title`, `branch_code`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_address`, `dependents`, `profile_photo_url`, `created_at`, `updated_at`) VALUES
(101, 'EMP-101', 'John', 'Doe', 'John Doe', 'Senior Manager', 2, NULL, NULL, '2022-01-10', '1985-05-20', 'john.doe@example.com', '03001234567', 'active', '123 Main St, Anytown', 'PK', 'Pakistani', '12345-1234567-1', '1234567-8', 80000.00, NULL, 'PKR', 5.00, '2025-01', 1.50, 'Bank of America', 'PK1234567890', 'John Doe', '1234', 'Jane Doe', 'Wife', '03009876543', '123 Main St, Anytown', '[{\"name\":\"Junior Doe\",\"relationship\":\"Son\",\"occupation\":\"Student\",\"dob\":\"2010-01-01\"}]', NULL, '2025-09-21 18:18:05', '2025-09-21 18:18:05'),
(102, 'EMP-102', 'Jane', 'Smith', 'Jane Smith', 'Financial Analyst', 3, NULL, NULL, '2023-03-15', '1990-11-25', 'jane.smith@example.com', '03007654321', 'active', '456 Oak Ave, Othercity', 'PK', 'Pakistani', '98765-4321098-7', '8765432-1', 60000.00, NULL, 'PKR', 0.00, '2024-03', 1.00, 'Allied Bank', 'PK9876543210', 'Jane Smith', '5678', 'John Smith', 'Husband', '03001112222', '456 Oak Ave, Othercity', '[]', NULL, '2025-09-21 18:18:05', '2025-09-21 18:18:05'),
(103, 'EMP-103', 'Alice', 'Williams', 'Alice Williams', 'Marketing Coordinator', 4, NULL, NULL, '2024-06-01', '1998-08-10', 'alice.williams@example.com', '03005556666', 'resigned', '789 Pine Ln, Somewhere', 'PK', 'Pakistani', '56789-0123456-7', '9988776-6', 45000.00, NULL, 'PKR', 0.00, '2025-06', 1.00, 'Meezan Bank', 'PK1122334455', 'Alice Williams', '9012', 'Michael Williams', 'Brother', '03004445555', '789 Pine Ln, Somewhere', '[]', NULL, '2025-09-21 18:18:05', '2025-09-21 18:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `document_category` varchar(255) NOT NULL,
  `document_title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_financial_transactions`
--

CREATE TABLE `employee_financial_transactions` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `transaction_type` enum('earning','deduction') NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_notes`
--

CREATE TABLE `employee_notes` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `author_id` int(11) UNSIGNED NOT NULL,
  `note_title` varchar(255) NOT NULL,
  `note_text` text NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fnf_settlements`
--

CREATE TABLE `fnf_settlements` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `termination_date` date NOT NULL,
  `status` enum('INITIATED','PENDING_DEPT_APPROVAL','PENDING_HR_PROCESSING','PENDING_PAYMENT','COMPLETED') NOT NULL DEFAULT 'INITIATED',
  `no_dues_status` enum('PENDING','CLEARED','NOT_APPLICABLE') NOT NULL DEFAULT 'PENDING',
  `net_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fnf_settlements`
--

INSERT INTO `fnf_settlements` (`id`, `employee_id`, `termination_date`, `status`, `no_dues_status`, `net_amount`, `created_at`, `updated_at`) VALUES
(1, 103, '2025-09-01', 'INITIATED', 'PENDING', 0.00, '2025-09-02 10:00:00', '2025-09-02 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

CREATE TABLE `payroll_details` (
  `id` int(11) UNSIGNED NOT NULL,
  `payroll_id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `payslip_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payslip_data`)),
  `status` enum('Pending','Needs Review','Approved') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_history`
--

CREATE TABLE `payroll_history` (
  `id` int(11) UNSIGNED NOT NULL,
  `pay_period_month` int(2) NOT NULL,
  `pay_period_year` int(4) NOT NULL,
  `status` enum('Pending','Finalized') NOT NULL DEFAULT 'Pending',
  `total_employees` int(11) NOT NULL,
  `total_gross_pay` decimal(15,2) NOT NULL,
  `total_deductions` decimal(15,2) NOT NULL,
  `total_net_pay` decimal(15,2) NOT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_templates`
--

CREATE TABLE `payroll_templates` (
  `id` int(11) UNSIGNED NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `department_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`department_ids`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_categories`
--

CREATE TABLE `reimbursement_categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reimbursement_categories`
--

INSERT INTO `reimbursement_categories` (`id`, `name`, `created_at`) VALUES
(1, 'Travel', '2025-09-21 18:18:05'),
(2, 'Food & Beverage', '2025-09-21 18:18:05'),
(3, 'Accommodation', '2025-09-21 18:18:05'),
(4, 'Supplies', '2025-09-21 18:18:05'),
(5, 'Client Entertainment', '2025-09-21 18:18:05'),
(6, 'Software Subscriptions', '2025-09-21 18:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_claims`
--

CREATE TABLE `reimbursement_claims` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `claim_title` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PKR',
  `status` enum('Pending','Approved','Rejected','Needs Correction') NOT NULL DEFAULT 'Pending',
  `supervisor_notes` text DEFAULT NULL,
  `processed_by` int(11) UNSIGNED DEFAULT NULL,
  `processed_date` date DEFAULT NULL,
  `submission_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_line_items`
--

CREATE TABLE `reimbursement_line_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `claim_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `expense_date` date NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_slabs`
--

CREATE TABLE `tax_slabs` (
  `id` int(11) UNSIGNED NOT NULL,
  `slab_name` varchar(255) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `minimum_amount` decimal(15,2) NOT NULL,
  `maximum_amount` decimal(15,2) DEFAULT NULL,
  `fixed_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `rate_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_slabs`
--

INSERT INTO `tax_slabs` (`id`, `slab_name`, `country_code`, `minimum_amount`, `maximum_amount`, `fixed_amount`, `rate_percentage`, `created_at`) VALUES
(1, 'Tax-Free', 'PK', 0.00, 600000.00, 0.00, 0.00, '2025-09-21 18:18:05'),
(2, 'Slab 1', 'PK', 600001.00, 1200000.00, 0.00, 2.50, '2025-09-21 18:18:05'),
(3, 'Slab 2', 'PK', 1200001.00, 2400000.00, 15000.00, 12.50, '2025-09-21 18:18:05'),
(4, 'Slab 3', 'PK', 2400001.00, 3600000.00, 165000.00, 20.00, '2025-09-21 18:18:05'),
(5, 'Slab 4', 'PK', 3600001.00, 6000000.00, 405000.00, 25.00, '2025-09-21 18:18:05'),
(6, 'Slab 5', 'PK', 6000001.00, 12000000.00, 1005000.00, 32.50, '2025-09-21 18:18:05'),
(7, 'Slab 6', 'PK', 12000001.00, NULL, 2955000.00, 35.00, '2025-09-21 18:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `business_reg` varchar(255) DEFAULT NULL,
  `business_country` varchar(255) DEFAULT NULL,
  `terms_agreed` tinyint(1) NOT NULL DEFAULT 0,
  `newsletter_subscribed` tinyint(1) NOT NULL DEFAULT 0,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`permissions`)),
  `role` enum('super-admin','admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `first_name`, `last_name`, `country`, `business_name`, `business_type`, `business_reg`, `business_country`, `terms_agreed`, `newsletter_subscribed`, `avatar_url`, `created_at`, `updated_at`, `permissions`, `role`) VALUES
(5, 'test@user.com', '$2y$10$mwcBR8ICyYmKBKLapIgbR..jxEQohfaAxGREf.YOfsqRN5wDfNyUe', 'Umar Ali', 'Umar', 'Ali', 'PK', 'Business', 'sole', '123465', 'PK', 1, 1, NULL, '2025-09-22 10:20:19', '2025-09-22 10:48:31', '[]', 'super-admin'),
(6, 'Super@admin.com', '$2y$10$/3Mz47iZiJ2jHr9FeUygoe7rBNZShi6d/HAPjKCnNEsMFcf.LxpRq', 'Super Admin', 'Super', 'Admin', 'PK', 'NEXT', 'sole', '12345', 'PK', 1, 1, NULL, '2025-09-22 10:49:44', '2025-09-22 10:52:15', '[]', 'super-admin');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `app_id` varchar(255) NOT NULL,
  `access_type` enum('view','edit','delete','create') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `contact_email` (`contact_email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_financial_transactions`
--
ALTER TABLE `employee_financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `fnf_settlements`
--
ALTER TABLE `fnf_settlements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_employee` (`payroll_id`,`employee_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_history`
--
ALTER TABLE `payroll_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pay_period` (`pay_period_month`,`pay_period_year`);

--
-- Indexes for table `payroll_templates`
--
ALTER TABLE `payroll_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reimbursement_categories`
--
ALTER TABLE `reimbursement_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `reimbursement_line_items`
--
ALTER TABLE `reimbursement_line_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `claim_id` (`claim_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tax_slabs`
--
ALTER TABLE `tax_slabs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slab_country` (`slab_name`,`country_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_app_access` (`user_id`,`app_id`,`access_type`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_financial_transactions`
--
ALTER TABLE `employee_financial_transactions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_notes`
--
ALTER TABLE `employee_notes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fnf_settlements`
--
ALTER TABLE `fnf_settlements`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_history`
--
ALTER TABLE `payroll_history`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_templates`
--
ALTER TABLE `payroll_templates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reimbursement_categories`
--
ALTER TABLE `reimbursement_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reimbursement_line_items`
--
ALTER TABLE `reimbursement_line_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_slabs`
--
ALTER TABLE `tax_slabs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_financial_transactions`
--
ALTER TABLE `employee_financial_transactions`
  ADD CONSTRAINT `employee_financial_transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD CONSTRAINT `employee_notes_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fnf_settlements`
--
ALTER TABLE `fnf_settlements`
  ADD CONSTRAINT `fnf_settlements_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD CONSTRAINT `payroll_details_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payroll_details_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  ADD CONSTRAINT `reimbursement_claims_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reimbursement_claims_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
