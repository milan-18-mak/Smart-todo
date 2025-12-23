-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 03:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studygenius`
--

-- --------------------------------------------------------

--
-- Table structure for table `missions`
--

CREATE TABLE `missions` (
  `id` int(11) NOT NULL,
  `mission_name` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `deadline` date NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `progress` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `missions`
--

INSERT INTO `missions` (`id`, `mission_name`, `subject`, `priority`, `deadline`, `status`, `progress`, `created_at`) VALUES
(9, 'XYZ', 'Web', 'Low', '2025-10-16', 'Completed', 100, '2025-10-10 08:57:41'),
(11, 'Java Component ', 'Java', 'High', '2025-10-18', 'Completed', 100, '2025-10-10 06:14:53'),
(13, 'Python Statement', 'Web', 'Medium', '2025-10-09', 'Completed', 100, '2025-10-10 06:17:05'),
(15, 'Exception', 'Java', 'High', '2025-10-07', 'Completed', 100, '2025-10-10 07:28:54'),
(16, 'adsa', 'dasd', 'Medium', '2025-09-29', 'Completed', 100, '2025-10-10 07:32:02'),
(17, 'dasdasd', 'dsd', 'Medium', '2025-10-24', 'Completed', 100, '2025-10-10 07:32:10'),
(18, 'dsda', 'ds', 'High', '2025-10-25', 'Completed', 100, '2025-10-10 07:32:22'),
(20, 'Exception', 'Java', 'Low', '2025-10-12', 'Pending', 0, '2025-10-10 19:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `photo`, `created_at`) VALUES
(1, 'Pratik', 'pratikghediya3@gmail.com', '$2y$10$EHg8gLozdrjSnnr3Am6uU.pNa.KF8i4luYDzg.E1L54arhnAq8DYO', 'uploads/1760017054_WhatsApp Image 2025-10-08 at 17.00.59_05d09f6b.jpg', '2025-10-09 13:37:35'),
(2, 'Milan', 'vimal@gmail.com', '$2y$10$CeYHBAQFf2xm6ZwA5LpU/e.oLd9L/81C/uEBpicwNfhRmrKNjI2Ue', 'uploads/1760017307_1000255518.jpg', '2025-10-09 13:41:47'),
(3, 'Ghediya', 'prince@gmail.com', '$2y$10$qUA.OYEehgpZ9PvXbnqVWek3AWDDjl1RFpURGiKsK9U0kBrrddHHa', 'uploads/1760064457_11.jpg', '2025-10-10 02:47:37'),
(4, 'Kalpesh', 'kalpesh@gmail.com', '$2y$10$MsbPEFjBpCGWsZH8O0T7weIaFdyUNnhMjnIekqJQhSZBD2yV4eitC', 'uploads/1760070011_11.jpg', '2025-10-10 04:20:11'),
(5, 'Kartik', 'Kartik@gmail.com', '$2y$10$/Jw0JqXqaU/fuNjwmhxl5OfjchkdoGquuZ7agP94z8Men3YWN4otu', 'uploads/1760115948_11.jpg', '2025-10-10 17:05:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
