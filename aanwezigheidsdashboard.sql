-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Gegenereerd op: 11 mrt 2025 om 08:45
-- Serverversie: 10.4.32-MariaDB
-- PHP-versie: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aanwezigheidsdashboard`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `attendance`
--

CREATE TABLE `attendance` (
  `teacher_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','meeting') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `tasks` text DEFAULT NULL,
  `hour` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `attendance`
--

INSERT INTO `attendance` (`teacher_id`, `date`, `status`, `reason`, `day`, `tasks`, `hour`) VALUES
(3, '2025-03-10', 'present', NULL, NULL, NULL, 0),
(4, '2025-03-10', 'present', NULL, NULL, NULL, 0),
(5, '2025-03-10', 'present', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `substitutes`
--

CREATE TABLE `substitutes` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `available` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `teachers`
--

INSERT INTO `teachers` (`id`, `name`) VALUES
(3, 'Jan Jansen'),
(4, 'Piet Pietersen'),
(5, 'Klaas Klaassen');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`teacher_id`,`date`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`,`date`,`hour`),
  ADD UNIQUE KEY `unique_attendance` (`teacher_id`,`day`,`hour`);

--
-- Indexen voor tabel `substitutes`
--
ALTER TABLE `substitutes`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `substitutes`
--
ALTER TABLE `substitutes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT voor een tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
