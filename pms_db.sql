-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 09:41 AM
-- Server version: 10.1.22-MariaDB
-- PHP Version: 7.1.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_name`) VALUES
(1, 'Amoxicillin'),
(4, 'Antibiotic'),
(5, 'Antihistamine'),
(8, 'Artesunate'),
(6, 'Atorvastatin'),
(9, 'Gentamicin'),
(3, 'Losartan'),
(2, 'Mefenamic'),
(7, 'Oxymetazoline');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_details`
--

CREATE TABLE `medicine_details` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `packing` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `medicine_details`
--

INSERT INTO `medicine_details` (`id`, `medicine_id`, `packing`) VALUES
(1, 1, '50'),
(2, 4, '50'),
(3, 5, '50'),
(4, 6, '25'),
(5, 3, '80'),
(6, 2, '100'),
(7, 7, '25'),
(8, 8, '80');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(60) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `assigned_doctor_id` int(11) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `allergies` text,
  `existing_conditions` text,
  `medical_history` text,
  `address` varchar(100) NOT NULL,
  `cnic` varchar(17) NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `gender` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_name`, `registration_date`, `assigned_doctor_id`, `admission_date`, `discharge_date`, `allergies`, `existing_conditions`, `medical_history`, `address`, `cnic`, `date_of_birth`, `phone_number`, `gender`) VALUES
(1, 'Ojo Daniella', '2025-11-05', 19, '2025-11-05', '2025-11-07', 'Nil', 'Typhoid fever', 'Haven\'t been diagnosed of any ailment for long.', 'Lagos', '402A', '1999-06-23', '091235649879', 'female'),
(6, 'George Bush', '2025-11-01', 19, '2025-11-01', '2025-11-06', 'Nil', 'Malaria', 'Been healthy', 'Texas', '0012', '2025-11-10', '09098765432', 'male'),
(10, 'John Kennedy', '2025-11-05', 19, '2025-11-05', '2025-11-06', 'nil', 'xyz', 'xyz', 'US', '0021', '1959-03-05', '08043454323', 'male'),
(11, 'Alaba Kehinde', '2025-11-05', 19, '2025-11-05', '2025-11-07', 'none', 'xyz', 'xyz', 'Offa', '0012', '1998-05-08', '091235649879', 'female');

-- --------------------------------------------------------

--
-- Table structure for table `patient_medication_history`
--

CREATE TABLE `patient_medication_history` (
  `id` int(11) NOT NULL,
  `patient_visit_id` int(11) NOT NULL,
  `medicine_details_id` int(11) NOT NULL,
  `quantity` tinyint(4) NOT NULL,
  `dosage` varchar(20) NOT NULL,
  `prescription_date` date DEFAULT NULL,
  `diagnosis` text,
  `frequency` varchar(100) DEFAULT NULL,
  `duration_days` int(11) DEFAULT '0',
  `instructions` varchar(255) DEFAULT NULL,
  `additional_notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `patient_medication_history`
--

INSERT INTO `patient_medication_history` (`id`, `patient_visit_id`, `medicine_details_id`, `quantity`, `dosage`, `prescription_date`, `diagnosis`, `frequency`, `duration_days`, `instructions`, `additional_notes`) VALUES
(2, 21, 1, 0, '2', '2025-11-05', 'Typhoid', 'thrice_daily', 5, 'after_meal', 'Drugs must be taken according to prescriptions.'),
(3, 24, 8, 0, '', '0000-00-00', 'Maleria', 'thrice_daily', 2, 'after_meal', 'xyz');

-- --------------------------------------------------------

--
-- Table structure for table `patient_visits`
--

CREATE TABLE `patient_visits` (
  `id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_type` varchar(32) NOT NULL DEFAULT 'check-up',
  `reason` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `booking_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `next_visit_date` date DEFAULT NULL,
  `bp` varchar(23) NOT NULL,
  `weight` varchar(12) NOT NULL,
  `disease` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `patient_visits`
--

INSERT INTO `patient_visits` (`id`, `visit_date`, `appointment_date`, `appointment_type`, `reason`, `status`, `booking_date`, `next_visit_date`, `bp`, `weight`, `disease`, `patient_id`) VALUES
(12, '2025-11-06', '2025-11-06', 'consultation', 'Consultancy', 'confirmed', '2025-11-05 12:13:18', NULL, '', '', '', 1),
(17, '2025-11-06', '2025-11-06', 'follow-up', 'Follow up on previous medications', 'confirmed', '2025-11-05 14:38:30', NULL, '', '', '', 6),
(19, '2025-11-05', '2025-11-05', 'emergency', 'Treatments', 'pending', '2025-11-05 16:10:46', NULL, '', '', '', 10),
(20, '2025-11-05', '2025-11-05', 'check-up', 'To show doctor a scan result', 'cancelled', '2025-11-05 16:44:50', NULL, '', '', '', 11),
(21, '0000-00-00', NULL, 'check-up', NULL, 'pending', '2025-11-05 16:48:07', '0000-00-00', '120/80', '55', 'Typhoid fever', 11),
(24, '0000-00-00', NULL, 'check-up', NULL, 'pending', '2025-11-05 17:08:29', '0000-00-00', '120/80', '55', 'Maleria', 6);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `display_name` varchar(30) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profile_picture` varchar(40) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'patient',
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `display_name`, `user_name`, `password`, `profile_picture`, `role`, `email`) VALUES
(1, 'Administrator', 'admin', '$2y$10$Vt0vwWxyfJdvrE8M5YjHJ.RotjaD6hHMAThTC871akrRbc3acRYby', '1656551981avatar.png ', 'admin', 'admin@test.com'),
(19, 'Doctor Ben', 'Ben', '$2y$10$N1HXLaW0YO8aStU1AfreHeB5O06EKV1i8pz4ZWTo940W3P/6Rlpqa', '1762340124_46aec268.jpg', 'doctor', 'doc@test.com'),
(21, 'John Kennedy', 'patient10', '$2y$10$x8hRbCVB7.pij6xyisGSMePF9vm8/jfNPLgxGs08Kr4l7YIHjK9gS', '', 'patient', 'ken@test.com'),
(22, 'Pharmacist', 'Pharmacist', '$2y$10$RJSyGIajbDCjs/GpL9vqleqViHlXt65gR8WzrBpsCwW.Hy8nYMA..', '1762355698_58df440c.png', 'pharmacy', 'mail.codeitng@gmail.com'),
(23, 'Alaba Kehinde', 'patient11', '$2y$10$US6RQqHocR5shqjenAd3QOcI8iDKZnJr/rkhmi0od.Ndg.3UObyfq', '1762357338_98e72ff7.png', 'patient', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_name` (`medicine_name`);

--
-- Indexes for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_medicine_details_medicine_id` (`medicine_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patient_medication_history_patients_visits_id` (`patient_visit_id`),
  ADD KEY `fk_patient_medication_history_medicine_details_id` (`medicine_details_id`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patients_visit_patient_id` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `medicine_details`
--
ALTER TABLE `medicine_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD CONSTRAINT `fk_medicine_details_medicine_id` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  ADD CONSTRAINT `fk_patient_medication_history_medicine_details_id` FOREIGN KEY (`medicine_details_id`) REFERENCES `medicine_details` (`id`),
  ADD CONSTRAINT `fk_patient_medication_history_patients_visits_id` FOREIGN KEY (`patient_visit_id`) REFERENCES `patient_visits` (`id`);

--
-- Constraints for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD CONSTRAINT `fk_patients_visit_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
