-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2025 at 08:57 AM
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
-- Database: `evoteplatform`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('admin','superadmin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `profile_image`, `role`, `created_at`) VALUES
(1, 'Will', 'will@gmail.com', '$2y$10$mnB/Y6z//UeQ5NESRpltcOloVwkfaybEUv5d.n9pIIZrFFjxPuYIC', 'WILLIAM', '../images/will.jpg', 'admin', '2025-05-12 12:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(50) NOT NULL,
  `election_id` int(50) NOT NULL,
  `candidate_name` varchar(100) NOT NULL,
  `image_path` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `election_id`, `candidate_name`, `image_path`) VALUES
(64, 1, 'jack', '../uploads/candidates/cand_685a1f3dea6c02.40662341_jack.jpg'),
(67, 16, 'jonah', '../uploads/candidates/cand_685ee48451dee6.86396847_jonah.jpg'),
(68, 16, 'Jannete', '../uploads/candidates/cand_685ee4a4245034.80994664_Jannete.jpg'),
(69, 1, 'Jacqueline', '../uploads/candidates/cand_687548c41694a9.16636198_Jacqueline.jpg'),
(71, 22, 'Robin', '../uploads/candidates/cand_687bd7f215f1f5.86061338_Robin.jpg'),
(72, 22, 'Jackson', '../uploads/candidates/cand_687bd80c255369.68820903_Jackson.jpg'),
(73, 23, 'Martin', '../uploads/candidates/cand_687bd83a7ca086.34264650_Martin.jpg'),
(74, 23, 'Martha', '../uploads/candidates/cand_687bd8566f4e23.48529480_Martha.jpg'),
(75, 24, 'Dan', '../uploads/candidates/cand_687bd882414896.32063865_Dan.jpg'),
(76, 24, 'Chris', '../uploads/candidates/cand_687bd8a1ac5388.03384297_Chris.jpg'),
(77, 24, 'Jonathan', '../uploads/candidates/cand_687bd8e08ee700.90284962_Jonathan.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('upcoming','active','completed') NOT NULL DEFAULT 'upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id`, `title`, `start_date`, `end_date`, `created_by`, `description`, `status`) VALUES
(1, 'Class Co-ordinators', '2025-08-11 00:00:00', '2025-08-12 00:00:00', 1, 'Class representative', 'upcoming'),
(16, 'Mayor', '2025-06-27 21:36:00', '2025-07-17 23:36:00', 1, 'topa', 'completed'),
(22, 'Governor', '2025-10-19 09:30:00', '2025-10-19 16:31:00', 1, 'Top guy', 'upcoming'),
(23, 'Faculty Rep', '2025-10-19 09:30:00', '2025-10-19 16:31:00', 1, 'Middle guy', 'upcoming'),
(24, 'Mr UMU', '2025-07-19 20:35:00', '2025-07-20 20:35:00', 1, 'The current guy', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'vote', 'You have successfully voted in the election: ', 1, '2025-06-10 07:11:36'),
(2, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'resolved\'.', 1, '2025-06-12 12:09:47'),
(3, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'in_progress\'.', 1, '2025-06-12 12:56:06'),
(4, 1, 'ticket_update', 'Your support ticket #2 status has been updated to \'in_progress\'.', 1, '2025-06-12 13:39:04'),
(5, 1, 'ticket_update', 'Your support ticket #2 status has been updated to \'in_progress\'.', 1, '2025-06-12 13:39:10'),
(6, 1, 'ticket_update', 'Your support ticket #1 status has been updated to \'resolved\'.', 1, '2025-06-12 13:39:32'),
(7, 1, 'announcement', 'A new election titled \'Mrs UMU\' has been created!', 1, '2025-06-12 14:05:40'),
(8, 1, 'vote', 'You have successfully voted in the election: Mrs UMU', 1, '2025-06-12 17:21:37'),
(9, 1, 'ticket_update', 'Your support ticket #2 status has been updated to \'resolved\'.', 1, '2025-06-13 10:18:24'),
(10, 1, 'ticket_update', 'Your support ticket #2 status has been updated to \'resolved\'.', 1, '2025-06-13 10:18:29'),
(11, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'resolved\'.', 1, '2025-06-14 17:52:53'),
(12, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'open\'.', 1, '2025-06-17 18:29:14'),
(13, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'open\'.', 1, '2025-06-17 18:29:17'),
(14, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'in_progress\'.', 1, '2025-06-18 18:12:03'),
(15, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'open\'.', 1, '2025-06-22 17:21:09'),
(16, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'open\'.', 1, '2025-06-22 17:21:11'),
(17, 1, 'announcement', 'A new election titled \'WISE\' has been created!', 1, '2025-06-22 17:23:03'),
(18, 1, 'vote', 'You have successfully voted in the election: WISE', 1, '2025-06-23 16:29:38'),
(19, 1, 'ticket_update', 'Your support ticket #4 status has been updated to \'in_progress\'.', 1, '2025-06-23 17:22:14'),
(20, 1, 'announcement', 'A new election titled \'Retake\' has been created!', 1, '2025-06-24 12:02:00'),
(21, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'in_progress\'.', 1, '2025-06-24 14:56:03'),
(22, 1, 'ticket_update', 'Your support ticket #4 status has been updated to \'resolved\'.', 1, '2025-06-24 15:03:20'),
(23, 1, 'ticket_update', 'Your support ticket #7 status has been updated to \'in_progress\'.', 1, '2025-06-24 15:03:42'),
(24, 1, 'ticket_update', 'Your support ticket #7 status has been updated to \'in_progress\'.', 1, '2025-06-24 15:09:11'),
(25, 1, 'ticket_update', 'Your support ticket #9 status has been updated to \'open\'.', 1, '2025-06-24 15:22:17'),
(26, 1, 'ticket_update', 'Your support ticket #3 status has been updated to \'resolved\'.', 1, '2025-06-24 15:22:27'),
(27, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'in_progress\'.', 1, '2025-06-24 15:23:25'),
(28, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:23:26'),
(29, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:23:26'),
(30, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:23:27'),
(31, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:23:27'),
(32, 1, 'ticket_update', 'Your support ticket #6 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:24:06'),
(33, 1, 'ticket_update', 'Your support ticket #5 status has been updated to \'open\'.', 1, '2025-06-24 15:27:55'),
(34, 1, 'ticket_update', 'Your support ticket #7 status has been updated to \'resolved\'.', 0, '2025-06-24 15:41:36'),
(35, 1, 'ticket_update', 'Your support ticket #8 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:52:10'),
(36, 1, 'ticket_update', 'Your support ticket #8 status has been updated to \'in_progress\'.', 0, '2025-06-24 15:52:12'),
(37, 1, 'ticket_update', 'Your support ticket #7 status has been updated to \'open\'.', 0, '2025-06-24 15:57:15'),
(38, 1, 'ticket_update', 'Your support ticket #8 status has been updated to \'open\'.', 1, '2025-06-24 16:27:24'),
(39, 1, 'vote', 'You have successfully voted in the election: Retake', 0, '2025-06-24 16:54:04'),
(40, 1, 'ticket_update', 'Your support ticket #9 status has been updated to \'in_progress\'.', 1, '2025-06-25 17:09:53'),
(41, 4, 'vote', 'You have successfully voted in the election: Retake', 0, '2025-06-25 18:55:35'),
(42, 8, 'vote', 'You have successfully voted in the election: Retake', 1, '2025-06-26 08:03:26'),
(43, 5, 'vote', 'You have successfully voted in the election: Retake', 0, '2025-06-26 20:22:09'),
(44, 1, 'ticket_update', 'Your support ticket #10 status has been updated to \'open\'.', 1, '2025-06-27 18:13:46'),
(45, 1, 'announcement', 'A new election titled \'Mayor\' has been created!', 1, '2025-06-27 18:34:50'),
(46, 4, 'vote', 'You have successfully voted in the election: Mayor', 0, '2025-06-27 18:39:57'),
(47, 8, 'ticket_update', 'Your support ticket #11 status has been updated to \'resolved\'.', 1, '2025-07-01 08:59:48'),
(48, 1, 'ticket_update', 'Your support ticket #10 status has been updated to \'in_progress\'.', 1, '2025-07-01 18:25:21'),
(49, 1, 'ticket_update', 'Your support ticket #10 status has been updated to \'resolved\'.', 0, '2025-07-05 09:39:07'),
(50, 1, 'ticket_update', 'Your support ticket #9 status has been updated to \'resolved\'.', 1, '2025-07-05 13:53:42'),
(51, 8, 'vote', 'You have successfully voted in the election: Mrs UMU', 0, '2025-07-08 11:50:00'),
(52, 1, 'ticket_update', 'Your support ticket #8 status has been updated to \'in_progress\'.', 0, '2025-07-14 18:14:21'),
(53, 1, 'vote', 'You have successfully voted in the election: Mrs UMU', 0, '2025-07-14 18:18:18'),
(54, 8, 'vote', 'You have successfully voted in the election: Mayor', 0, '2025-07-17 12:40:34'),
(55, 5, 'vote', 'You have successfully voted in the election: Mayor', 0, '2025-07-17 13:05:15'),
(56, 1, 'vote', 'You have successfully voted in the election: Mayor', 1, '2025-07-17 13:29:37'),
(57, 8, 'ticket_update', 'Your support ticket #12 status has been updated to \'in_progress\'.', 0, '2025-07-17 16:00:36'),
(58, 1, 'announcement', 'A new election titled \'qwer\' has been created!', 0, '2025-07-18 12:35:20'),
(59, 1, 'ticket_update', 'Your support ticket #13 status has been updated to \'in_progress\'.', 0, '2025-07-18 13:15:41'),
(60, 1, 'announcement', 'A new election titled \'asdfgh\' has been created!', 0, '2025-07-18 13:38:47'),
(61, 1, 'announcement', 'A new election titled \'ewrty\' has been created!', 0, '2025-07-18 14:17:41'),
(62, 1, 'announcement', 'A new election titled \'skrgn\' has been created!', 1, '2025-07-18 14:56:03'),
(63, 1, 'announcement', 'A new election titled \'sdfgh\' has been created!', 1, '2025-07-18 14:58:00'),
(64, 1, 'announcement', 'A new election titled \'Governor\' has been created!', 0, '2025-07-19 17:32:59'),
(65, 1, 'announcement', 'A new election titled \'Faculty Rep\' has been created!', 0, '2025-07-19 17:34:29'),
(66, 1, 'announcement', 'A new election titled \'Mr UMU\' has been created!', 0, '2025-07-19 17:36:03'),
(67, 1, 'vote', 'You have successfully voted in the election: Mr UMU', 0, '2025-07-19 17:47:35'),
(68, 4, 'vote', 'You have successfully voted in the election: Mr UMU', 0, '2025-07-19 18:47:12'),
(69, 8, 'vote', 'You have successfully voted in the election: Mr UMU', 0, '2025-07-20 05:56:55'),
(70, 5, 'vote', 'You have successfully voted in the election: Mr UMU', 0, '2025-07-20 06:10:18'),
(71, 14, 'vote', 'You have successfully voted in the election: Mr UMU', 0, '2025-07-20 06:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'voter',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `username`, `image`, `email`, `password`, `full_name`, `role`, `status`, `created_at`) VALUES
(1, 'Jane', '../images/propic14.jpg', 'njane@gmail.com', '$2y$10$IIdb.FD4Wh7de6g.D6UoGOuXdBsadI7OozcZBv4lI7AE8X8a08smC', 'Nakatudde Jane', 'voter', 'active', '2025-05-23 17:44:45'),
(4, 'Alex', '../images/propic13.jpg', 'alex@gmail.com', '$2y$10$EbwFc6wU9GW9kVOqn.xk1.Po7F4t1jvaUi4YWlBjdLD6agg.awTKq', 'Sewava Alex', 'voter', 'active', '2025-06-08 17:38:14'),
(5, 'Shifran', '../images/propic15.jpg', 'shifran@gmail.com', '$2y$10$XiruJxR0nTOsqYJrxXwArOKJt5pYWGOWjgTWfWJVsuM3QvH3WJ9hG', 'Shifran Nampi', 'voter', 'active', '2025-06-18 13:56:51'),
(8, 'Eric', '../images/imag2.jpg', 'eric@gmail.com', '$2y$10$2mMVQ/sz2/lx5r6eKjxCDeEyNB2oBB0AL2Ckz/4XCGFgy.KnlHE3u', 'Eric weasal', 'voter', 'active', '2025-06-22 18:14:03'),
(14, 'John', '../images/propic7.jpg', 'john@gmail.com', '$2y$10$vUfeKf9C0S6h8lHWqHvG0eGilhChWYwm7k3poBECJbFXwDU6Lfxia', 'John Musoke', 'voter', 'active', '2025-07-19 17:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `vote_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `election_id`, `candidate_id`, `vote_count`) VALUES
(7, 16, 68, 1),
(10, 16, 67, 1),
(11, 16, 68, 1),
(12, 16, 68, 1),
(13, 24, 75, 1),
(14, 24, 76, 1),
(15, 24, 77, 1),
(16, 24, 76, 1),
(17, 24, 76, 1);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 1, 'Login', 'My login takes long to submit', 'resolved', '2025-06-10 08:02:58'),
(2, 1, 'ytyghb', 'fyfgguyh', 'resolved', '2025-06-10 09:42:06'),
(3, 1, 'fgch', 'fghjkilo', 'resolved', '2025-06-10 10:33:39'),
(4, 1, 'name', 'wrong spelling', 'resolved', '2025-06-22 22:59:03'),
(5, 1, 'Appreciation', 'Thank you', 'open', '2025-06-24 12:32:06'),
(6, 1, 'Voting', 'when does it start', 'in_progress', '2025-06-24 13:59:42'),
(7, 1, 'Help', 'My pages are slow', 'open', '2025-06-24 14:01:13'),
(8, 1, 'sad', '\\zsdfs', 'in_progress', '2025-06-24 14:02:23'),
(9, 1, 'sdf', 'asfdg', 'resolved', '2025-06-24 14:04:03'),
(10, 1, 'sewdd', 'wsdfg', 'resolved', '2025-06-24 16:57:06'),
(11, 8, 'profile', 'can i edit my username', 'resolved', '2025-06-27 18:22:38'),
(12, 8, 'Login', 'is too slow', 'in_progress', '2025-07-01 18:31:16'),
(13, 1, 'visibility', 'Why can i see you', 'in_progress', '2025-07-17 14:28:44'),
(14, 14, 'Appreciation', 'You just made voting easy', 'open', '2025-07-20 06:11:55');

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `id` int(11) NOT NULL,
  `register_id` varchar(50) NOT NULL,
  `has_voted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voters`
--

INSERT INTO `voters` (`id`, `register_id`, `has_voted`) VALUES
(2, '1', 0),
(3, '4', 0),
(4, '8', 0),
(5, '5', 0),
(6, '10', 0),
(7, '14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `voter_tracking`
--

CREATE TABLE `voter_tracking` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `voted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voter_tracking`
--

INSERT INTO `voter_tracking` (`id`, `election_id`, `voter_id`, `voted_at`) VALUES
(1, 14, 2, '2025-06-23 19:29:38'),
(2, 15, 2, '2025-06-24 19:54:04'),
(3, 15, 3, '2025-06-25 21:55:35'),
(4, 15, 4, '2025-06-26 11:03:26'),
(5, 15, 5, '2025-06-26 23:22:09'),
(6, 16, 3, '2025-06-27 21:39:57'),
(7, 13, 4, '2025-07-08 14:50:00'),
(8, 13, 2, '2025-07-14 21:18:18'),
(9, 16, 4, '2025-07-17 15:40:34'),
(10, 16, 5, '2025-07-17 16:05:15'),
(11, 16, 2, '2025-07-17 16:29:37'),
(12, 24, 2, '2025-07-19 20:47:35'),
(13, 24, 3, '2025-07-19 21:47:12'),
(14, 24, 4, '2025-07-20 08:56:55'),
(15, 24, 5, '2025-07-20 09:10:18'),
(16, 24, 7, '2025-07-20 09:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `election_id`, `candidate_id`, `timestamp`) VALUES
(1, 14, 62, '2025-06-23 19:29:38'),
(2, 15, 65, '2025-06-24 19:54:04'),
(3, 15, 66, '2025-06-25 21:55:35'),
(4, 15, 65, '2025-06-26 11:03:26'),
(5, 15, 66, '2025-06-26 23:22:09'),
(6, 16, 68, '2025-06-27 21:39:57'),
(7, 13, 61, '2025-07-08 14:50:00'),
(8, 13, 61, '2025-07-14 21:18:18'),
(9, 16, 67, '2025-07-17 15:40:34'),
(10, 16, 68, '2025-07-17 16:05:15'),
(11, 16, 68, '2025-07-17 16:29:37'),
(12, 24, 75, '2025-07-19 20:47:35'),
(13, 24, 76, '2025-07-19 21:47:12'),
(14, 24, 77, '2025-07-20 08:56:55'),
(15, 24, 76, '2025-07-20 09:10:18'),
(16, 24, 76, '2025-07-20 09:12:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_id` (`election_id`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elections_ibfk_1` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_id` (`election_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `voters`
--
ALTER TABLE `voters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `register_id` (`register_id`);

--
-- Indexes for table `voter_tracking`
--
ALTER TABLE `voter_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `election_id` (`election_id`,`voter_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `voter_tracking`
--
ALTER TABLE `voter_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`);

--
-- Constraints for table `elections`
--
ALTER TABLE `elections`
  ADD CONSTRAINT `elections_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
