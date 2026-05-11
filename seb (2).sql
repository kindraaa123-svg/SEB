-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 08:47 PM
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
-- Database: `seb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `classid` int(11) NOT NULL,
  `classname` varchar(255) DEFAULT NULL,
  `majorid` int(11) NOT NULL,
  `gradeid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`classid`, `classname`, `majorid`, `gradeid`) VALUES
(1, 'A', 1, 1),
(2, 'B', 1, 1),
(3, 'C', 1, 1),
(4, 'A', 1, 2),
(5, 'B', 1, 2),
(6, 'C', 1, 2),
(7, 'A', 1, 3),
(8, 'B', 1, 3),
(9, 'C', 1, 3),
(10, '', 2, 4),
(11, '', 3, 4),
(12, '', 4, 4),
(13, '', 2, 5),
(14, '', 3, 5),
(15, '', 4, 5),
(16, '', 2, 6),
(17, '', 3, 6),
(18, 'A', 4, 6),
(19, 'B', 4, 6);

-- --------------------------------------------------------

--
-- Table structure for table `exam_codes`
--

CREATE TABLE `exam_codes` (
  `codeid` bigint(20) NOT NULL,
  `code` varchar(50) NOT NULL,
  `codetype` enum('entry','unlock','exit') NOT NULL,
  `supervisorid` bigint(11) NOT NULL,
  `expired_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_codes`
--

INSERT INTO `exam_codes` (`codeid`, `code`, `codetype`, `supervisorid`, `expired_at`, `created_at`, `updated_at`) VALUES
(34, 'K5AUFJ', 'entry', 1, '2026-05-12 01:37:43', '2026-05-12 01:27:43', '2026-05-12 01:27:43'),
(35, 'BNY2GN', 'entry', 1, '2026-05-12 01:41:05', '2026-05-12 01:31:05', '2026-05-12 01:31:05'),
(36, '7PADW3', 'exit', 1, '2026-05-12 01:49:09', '2026-05-12 01:39:09', '2026-05-12 01:39:09'),
(37, '3G9XCG', 'unlock', 1, '2026-05-12 01:49:52', '2026-05-12 01:39:52', '2026-05-12 01:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `gradeid` int(11) NOT NULL,
  `gradename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade`
--

INSERT INTO `grade` (`gradeid`, `gradename`) VALUES
(1, 'VII'),
(2, 'VIII'),
(3, 'IX'),
(4, 'X'),
(5, 'XI'),
(6, 'XII');

-- --------------------------------------------------------

--
-- Table structure for table `major`
--

CREATE TABLE `major` (
  `majorid` int(11) NOT NULL,
  `majorname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `major`
--

INSERT INTO `major` (`majorid`, `majorname`) VALUES
(1, 'SMP'),
(2, 'AKL'),
(3, 'BDP'),
(4, 'RPL');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_04_27_000003_create_access_codes_tables', 2),
(5, '2026_04_27_000004_create_exam_codes_table', 3),
(6, '2026_04_27_000005_enforce_unique_usage_per_student_per_code', 4);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('7jWMG9KJlSIT673pjR7HEoSDPDAhi43TKUSJOWJB', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiVWV1c0k3Z0piWFpoOHlrVm53ZFY4b2JEZXZOemwyd280VmUxeDVObCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9leGl0LWNvZGUiO3M6NToicm91dGUiO3M6OToiY29kZS5leGl0Ijt9czoxMDoic3VwZXJ2aXNvciI7YToyOntzOjI6ImlkIjtpOjE7czo4OiJ1c2VybmFtZSI7czo1OiJhZG1pbiI7fXM6MjM6InRpbWV6b25lX29mZnNldF9taW51dGVzIjtpOi00MjA7fQ==', 1778525225),
('VFfZJ2uOQIAKiVFvidl4LlQTO6tOZW8abWh4Vf7I', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiU21aaUdUcExxQnVTMVcwTGsxVTNjQUtEOXVoOElGUDZBMTczOFVoUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fX0=', 1778518602);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `studentid` int(11) NOT NULL,
  `nis` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `classid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studentid`, `nis`, `name`, `classid`) VALUES
(1, 25161050, 'ALVEN CAESAR SISWANTO', 10),
(2, 25161024, 'ANUGERAH ELSHADAI HAREFA', 10),
(3, 25161025, 'CHRIS CHELLA', 10),
(4, 25161045, 'DESICA', 10),
(5, 25161012, 'EDELYN', 10),
(6, 25161058, 'ENJELLIKA', 10),
(7, 25161016, 'ERIC GUNALI', 10),
(8, 25161039, 'FRANCES', 10),
(9, 25161002, 'JENNIFFER EVELINE TU', 10),
(10, 25161037, 'KEISYA ABELIA RAMADHANI', 10),
(11, 25161004, 'MADELINE', 10),
(12, 25161029, 'MEYLISA', 10),
(13, 25161019, 'MICHELLE', 10),
(14, 25161031, 'SHA SHIA TAN', 10),
(15, 25161042, 'SHEREN', 10),
(16, 25161014, 'SHERENE CALLYSTA', 10),
(17, 25161041, 'VALENTIA GOTAMI TAN', 10),
(18, 25161003, 'VANESSA THO', 10),
(19, 25161017, 'VILENCIA VANESSA', 10),
(20, 25161033, 'VIONA ENJELINA', 10),
(21, 25161007, 'BRAYDEN KANG', 12),
(22, 25161006, 'FELICIA KANG', 12),
(23, 25161005, 'JACKLY', 12),
(24, 25161015, 'JACQUELINA', 12),
(25, 25161018, 'JONATHAN', 12),
(26, 25161020, 'WHITNEY NG', 12),
(27, 25161022, 'DARRENT FUH', 12),
(28, 25161023, 'VINCENT FERNANDEZ', 12),
(29, 25161032, 'CARLIN FRANCISCO', 12),
(30, 25161036, 'JOYCE PUNDARIKA ZHEN', 12),
(31, 25161044, 'KELVIN', 12),
(32, 25161046, 'JANNEL VANESSA', 12),
(33, 25161026, 'IYAN', 12),
(34, 25161030, 'ZELLY CHRISTINA', 12),
(35, 25161047, 'ZANO MIRZALE AYANO', 12),
(36, 25161049, 'PRICILIA DAVIANA WONG', 12),
(37, 25161051, 'NABIL KASYFI AZWAN', 12),
(38, 25161054, 'GRACIA SILVIANA SIHOMBING', 12),
(39, 25161053, 'JESSICA SARWENDAH', 12),
(40, 25161055, 'SEPTIANA ELISABET', 12),
(41, 25161001, 'ANGELIN FELIM', 11),
(42, 25161035, 'ANGELINE TAN', 11),
(43, 25161027, 'ARIANTO', 11),
(44, 25161028, 'BRUCE LEONARDO', 11),
(45, 25161048, 'CELINE WONG', 11),
(46, 25161009, 'ELIS', 11),
(47, 25161043, 'GLADIES HARTONO PUTRI', 11),
(48, 25161010, 'JUWITA VANESSA', 11),
(49, 25161013, 'LYDIA ASHLEY WIJAYA', 11),
(50, 25161011, 'VELORINE JELVIANA', 11),
(51, 24141001, 'ALMIRA FAYZA SANJAYA', 4),
(52, 24141019, 'BRILLIAN BOSMAN TANAKO', 4),
(53, 24141036, 'CALVIN FELICIO CAN', 4),
(54, 24141012, 'CALVINSON NICHOLAS', 4),
(55, 24141074, 'CLEONARA MIROVERAEL LIM', 4),
(56, 24141020, 'DARREN ANDERSSON', 4),
(57, 24141050, 'DELVIN KOH', 4),
(58, 24141026, 'FEBRYAN', 4),
(59, 24141010, 'FERDYA FAUSTINA KANG', 4),
(60, 24141004, 'FERYKA YEO', 4),
(61, 24141037, 'FRAELIA EVERILDA SITORUS', 4),
(62, 24141049, 'IVANDER ORIANDO', 4),
(63, 24141064, 'JERYCO ARYA WIJAYA', 4),
(64, 24141030, 'KENNETH', 4),
(65, 24141053, 'KHERRICK ZHANG', 4),
(66, 24141055, 'MARVIN GUINEVER', 4),
(67, 24141013, 'NADIA CALLYSTA', 4),
(68, 24141046, 'RAYMOND', 4),
(69, 24141023, 'ROBIN SEPTIAWAN TAN', 4),
(70, 24141005, 'STANLEY ZHANG', 4),
(71, 24141047, 'VATYSSA TAN', 4),
(72, 24141021, 'VINCENT', 4),
(73, 24141006, 'WISLEY GARVINCENT', 4),
(74, 24141066, 'ZOFIA NG XIANG TING', 4),
(75, 24141063, 'AKIM', 5),
(76, 24141015, 'BOAS WELFRID LUMBANRAJA', 5),
(77, 24141029, 'BONG ALVIN ALCANTARA', 5),
(78, 24141025, 'CALLISTA', 5),
(79, 24141042, 'CATHY SHARON', 5),
(80, 24141048, 'CHARLES RICHARD', 5),
(81, 24141051, 'CHRISTOPHER MIKE', 5),
(82, 24141009, 'CLARA SHARON HUANG', 5),
(83, 24141016, 'DAVID TIO', 5),
(84, 24141034, 'FERDINAND MARVELLINO', 5),
(85, 24141039, 'FREDY', 5),
(86, 24141068, 'JASMIN KHIRANI AZKADINA', 5),
(87, 24141041, 'JOVELYN FLAVIA', 5),
(88, 24141072, 'KELLY AURORA', 5),
(89, 24141038, 'KEVIN STEVANO GUNAWAN', 5),
(90, 24141069, 'KEYSHIA HARLYN', 5),
(91, 24141059, 'LUIS FERNANDO', 5),
(92, 24141007, 'MALVIN LIM', 5),
(93, 24141062, 'SHELLY VALENCY', 5),
(94, 24141028, 'SINLING PARAMITHA', 5),
(95, 24141033, 'VINELLA FELICIA ROSWELL', 5),
(96, 24141035, 'YASINTA PUTRI AYU', 5),
(97, 24141067, 'ALFREDO VIGO RERUNG', 6),
(98, 24141061, 'ASARIAZAHRA ANDRIYANI JUNIAR', 6),
(99, 24141032, 'AULIA NILA PRATAMA', 6),
(100, 24141054, 'CHELSEA OLIVIA', 6),
(101, 24141052, 'CHERLYN ZHONG', 6),
(102, 24141071, 'DAVELLEY TORRESCA KHO', 6),
(103, 24141043, 'DERRICK NG', 6),
(104, 24141024, 'DYLAN CHRISTIANO', 6),
(105, 24141060, 'FARHAN MAULANA KHAIRONI', 6),
(106, 24141011, 'JUVEN MANDARAVA ZHEN', 6),
(107, 24141002, 'KENDRICK RYO LIM', 6),
(108, 24141044, 'KEVIN OKTAVIANO', 6),
(109, 24141040, 'LOUIS HENDERSON', 6),
(110, 24161020, 'RAMA ADITYA LUBIS', 6),
(111, 24141070, 'RAY HANN XAVERIUS CHAN', 6),
(112, 24141045, 'RESTINA PANG', 6),
(113, 24141014, 'SAMUEL ARCHILLES SIMANJUNTAK', 6),
(114, 24141065, 'VALENTINO ROCKY', 6),
(115, 24141057, 'VANESSA ANGEL', 6),
(116, 24161064, 'VECHARIO JELVENTIANO', 6),
(117, 24141031, 'WISLY WIJAYA', 6),
(118, 23141001, 'ANGEL LEE', 7),
(119, 23141035, 'BINTANG B. OMPU SUNGGU', 7),
(120, 23141005, 'CALLYSTA ELIXIA WIRIYANO KHANG', 7),
(121, 23141031, 'CASEY CIMBERLY', 7),
(122, 23141038, 'ENJELINE', 7),
(123, 23141040, 'FELYCIA CIUNG', 7),
(124, 23141029, 'FERDINAND RICHARDO', 7),
(125, 23141004, 'GARCIA VALESKA', 7),
(126, 23141026, 'GRACIA ANGELA MOY', 7),
(127, 23141021, 'HAYU WIDYA', 7),
(128, 23141015, 'JOECILIN MARIS', 7),
(129, 23141012, 'JUSTING YANG', 7),
(130, 23141023, 'LOVELY VALENCIA', 7),
(131, 23161006, 'NAUDY ANGELIN', 7),
(132, 23141002, 'OLIVIA JOLLIN LIEW EVE HWE MIN', 7),
(133, 23141022, 'STEVEN GAN', 7),
(134, 23141007, 'TIMOTHY JONATHAN', 7),
(135, 23141032, 'VIVIAN MILANI', 7),
(136, 23141010, 'WILLIAM ALVARO', 7),
(137, 23141033, 'ABDI BINAWIRATA', 8),
(138, 23141042, 'ALDRYAN DWIKA RADHISTY', 8),
(139, 23141018, 'ANDINI JOULI', 8),
(140, 24141073, 'CALLISTA PUTRI DEYVINTA', 8),
(141, 23141034, 'CAVELL', 8),
(142, 23141003, 'DEARLYNE', 8),
(143, 23141025, 'FELLYSIA WONG', 8),
(144, 23141027, 'GUSTIANO VAN VERCY', 8),
(145, 23141024, 'JESICCA', 8),
(146, 23141008, 'JOLVIN ARISKI', 8),
(147, 23141006, 'JUAN XANDER WIRIYANTO KHANG', 8),
(148, 23141016, 'JUSTIN VERGUSON ZHOU', 8),
(149, 23141020, 'KAYLA AMELIA', 8),
(150, 23141017, 'LIONEL POETRA', 8),
(151, 23141030, 'MITA', 8),
(152, 23141036, 'MOLY', 8),
(153, 23141019, 'NATASYA ANDITA', 8),
(154, 23141014, 'RANDY ALEXANDER YAP', 8),
(155, 23141037, 'WHITNEY ZHANG', 8),
(156, 23242001, 'ANITA', 9),
(157, 23242002, 'AYUTA', 9),
(158, 23242003, 'BERLINDA', 9),
(159, 23242004, 'BUDI SANTOSO', 9),
(160, 23242005, 'CHAROLINE AQUILA', 9),
(161, 23242006, 'CHELSY LIA', 9),
(162, 23242007, 'CHERYL HAYUI CELESTIN', 9),
(163, 23242008, 'DAVID NICHOLAS', 9),
(164, 23242009, 'DELVYNA', 9),
(165, 23242010, 'JECYLYN ZHONG', 9),
(166, 23242011, 'JESSLYN CHAN', 9),
(167, 23242012, 'JESSYNIA ADELINA', 9),
(168, 23242013, 'JOHANES', 9),
(169, 23242014, 'KATELYN', 9),
(170, 23242015, 'KEATY PEBIANA SARI', 9),
(171, 23242016, 'KIERA', 9),
(172, 23242017, 'MICHAEL BERRAND', 9),
(173, 23242018, 'NOVIA JACELYN', 9),
(174, 23242020, 'RYNA JAZLYN', 9),
(175, 23242021, 'SUDYHARTO', 9),
(176, 23242022, 'VIVIAN', 9),
(177, 23242023, 'WENDY KHO', 9),
(178, 23242024, 'ZEFANIA CHRISTINE', 9),
(179, 25141036, 'AURORA AMBERLEY ACELINTAN', 1),
(180, 25141002, 'DION ANGELLYN', 1),
(181, 25141026, 'EVELYN LEONG', 1),
(182, 25141039, 'FILLBERT OSMOND TAN', 1),
(183, 25141031, 'GISELLE FAUSTA DORNY', 1),
(184, 25141044, 'GLADIES CAROLINE NG', 1),
(185, 25141047, 'HUGO JULIO AZWAN', 1),
(186, 25141052, 'JASSON ANGELO', 1),
(187, 25141028, 'JAYDEN KINGSTON', 1),
(188, 25141030, 'JOLLYN ZHANG', 1),
(189, 25131005, 'KELLY YEO', 1),
(190, 25141009, 'LIONI NICHOLAS', 1),
(191, 25141014, 'LOUIS GELVIN WANG', 1),
(192, 25141053, 'LUCAS LEE', 1),
(193, 25141054, 'LUCAS ROVIO LEE', 1),
(194, 25141040, 'MICHELLE LU', 1),
(195, 25141043, 'NATAL TAN CIA CAI', 1),
(196, 25141050, 'SIMON JACK QWEN OLA KUMA', 1),
(197, 25141017, 'VANESSA ANGELITA', 1),
(198, 25141012, 'VINDIESEL SU', 1),
(199, 25141024, 'ZEREEN EDDLIN', 1),
(200, 25141025, 'CAROLYNE VERONICA', 2),
(201, 25141046, 'CULVER CHRISTIAN JACOUS', 2),
(202, 25141034, 'FILBERTA ADLINA', 2),
(203, 25141015, 'GENEVA TAN', 2),
(204, 25141022, 'JACKY', 2),
(205, 25141023, 'JEMY HUANG', 2),
(206, 25141033, 'JUNIUS MICHAEL XIAO', 2),
(207, 25141001, 'KELLYCIA YOVELA', 2),
(208, 25141037, 'LUCIA ALBERTA THEODORA', 2),
(209, 25141041, 'MICHEAL JONATHAN', 2),
(210, 25141038, 'MYKEYLA ZORA ZETA ZONE', 2),
(211, 25141051, 'NICHOLAS CHAI', 2),
(212, 25141035, 'NICOLE AMILANE', 2),
(213, 25161021, 'OLIVIA PACEWIN', 2),
(214, 25141056, 'RASELA AFIKA HALZAHRA', 2),
(215, 25141019, 'RYAN JACKSON', 2),
(216, 25141011, 'SALSABILA NADHIFA', 2),
(217, 25141048, 'VINCENT THEO YEO', 2),
(218, 25141003, 'ALICE OLIVIA', 3),
(219, 25141008, 'BELVA', 3),
(220, 25141049, 'CARLOS TOMSON', 3),
(221, 25141032, 'CHRIS JHOPAN', 3),
(222, 25141027, 'CHRISTINE ANGEL LA LIM', 3),
(223, 25141010, 'DERRIC ZHANG', 3),
(224, 25141016, 'EVAN SAPUTRA', 3),
(225, 25141007, 'EVELYN ZHONG', 3),
(226, 25141029, 'HARRISON HARTANTO', 3),
(227, 25141005, 'JESLYVIEN', 3),
(228, 25141006, 'JHON ANDERSON', 3),
(229, 25141045, 'LEONEL DANOVAN', 3),
(230, 25141004, 'NIKAYLA AUDREY BRILIAN', 3),
(231, 25141042, 'RAJA YUSUF DAVINO SIURIAN', 3),
(232, 25141055, 'RIZKI BIN AHMAD', 3),
(233, 24161057, 'ANGELA BEATRICE', 15),
(234, 24161065, 'ANISSA VIRIYA SATI', 15),
(235, 24161019, 'BRIAN EVAN LOUIS', 15),
(236, 24161012, 'BRYAN JONATHAN', 15),
(237, 24161033, 'HENDRIK HUANG', 15),
(238, 24161037, 'JAMES GOH', 15),
(239, 24161008, 'JESLYN', 15),
(240, 24161010, 'JONATHAN HUANG', 15),
(241, 24161038, 'JOVIAN', 15),
(242, 24161054, 'JOVIAN FAIRLAY', 15),
(243, 24161062, 'KINDRA AYU MEGUMI', 15),
(244, 24161030, 'LIONELIS NICHOLAS', 15),
(245, 24161009, 'LUIZ GARVINCENT', 15),
(246, 24161006, 'NATHALIA', 15),
(247, 24161029, 'NICKENT FAUSTA', 15),
(248, 24161066, 'NICOLAS TONG', 15),
(249, 24161025, 'RACHEL KEITLIN', 15),
(250, 24161036, 'RIKARDO ROMLAN PANGARIBUAN', 15),
(251, 24161021, 'ALICE KOH', 13),
(252, 24161005, 'ANGGELLYN LEE', 13),
(253, 24161034, 'CHESILYA NATASYA', 13),
(254, 24161013, 'DANIEL', 13),
(255, 25161056, 'DICKY', 13),
(256, 24161046, 'GRANDELIN', 13),
(257, 24161060, 'JASHINTA', 13),
(258, 24161011, 'JENNIFER', 13),
(259, 24161001, 'JESLYN VALENCIA', 13),
(260, 24161047, 'JOCELYN', 13),
(261, 24161002, 'JOYCE', 13),
(262, 24161063, 'JUVELO ANJELINA', 13),
(263, 24161055, 'KHANZA UMEKO VALLERIE GUNAWAN', 13),
(264, 24161015, 'MUHAMMAD GUNTUR KRISNA RAMADHAN', 13),
(265, 24161032, 'NICOLE NATALIA GAUTAMA', 13),
(266, 24161027, 'RAVINDRA GAUTAMA SYAHPUTRA', 13),
(267, 24161035, 'TIMAN SAPUTRA', 13),
(268, 23161062, 'VICTOR LAURENCE CHOW', 13),
(269, 24161031, 'WINNIE SEPTANIA', 13),
(270, 24161003, 'ZHANG JIA NI', 13),
(271, 24161058, 'AMELIA CAHYA', 14),
(272, 24161004, 'ANGEL', 14),
(273, 24161023, 'ANGELINA', 14),
(274, 25161038, 'BALQIS MALAEKA', 14),
(275, 24161048, 'CHELLY OLIVIA', 14),
(276, 24161039, 'CINDY', 14),
(277, 25161057, 'DAVIN JAIRUS', 14),
(278, 24161042, 'DEDI YANTO', 14),
(279, 24161014, 'EDWIN', 14),
(280, 24161007, 'ERICVIN', 14),
(281, 24161017, 'GALVIND WU', 14),
(282, 24161056, 'HERFIYAN', 14),
(283, 24161051, 'IVALENA', 14),
(284, 24161050, 'IVALENI', 14),
(285, 24161024, 'JACEFUNNY', 14),
(286, 24161043, 'JOEVANNA TAN', 14),
(287, 24161018, 'JUNI JESSYA', 14),
(288, 24161028, 'KRISTINA', 14),
(289, 24161040, 'KWO MEYIN', 14),
(290, 24161052, 'M. RIZDHAN PUTRA ZECHA', 14),
(291, 24161022, 'OBAMA JONATAN', 14),
(292, 24161044, 'RACHEL LAURA NG', 14),
(293, 24161026, 'SYLVIA', 14),
(294, 24161053, 'TRICIA AGATHA YANG', 14),
(295, 24161041, 'ZYZY', 14),
(296, 23161050, 'ANJAS SAPUTRA BIN AHMAD', 18),
(297, 23161013, 'CHLOE AQUINO', 18),
(298, 23161048, 'DENNIS', 18),
(299, 23161054, 'DERIUS', 18),
(300, 23161057, 'ERWIN', 18),
(301, 23161008, 'MEYLIANA', 18),
(302, 23161004, 'NATASHA GOTAMI', 18),
(303, 24161059, 'RIRIN YAN WEN LEE', 18),
(304, 23161061, 'RONI GABRIEL SITOMPUL', 18),
(305, 23161032, 'SELLIENA LINE DWI', 18),
(306, 23141064, 'SIM CHIU SHIA', 18),
(307, 23161053, 'STEVEN SES', 18),
(308, 23161025, 'STEVEN SUSANTO', 18),
(309, 23161022, 'WENDY', 18),
(310, 23161029, 'ANGEL NATASYA LASE', 19),
(311, 24161045, 'ANTHONY WELLSON', 19),
(312, 23161044, 'ANTONI', 19),
(313, 23161017, 'BUDI SUSANTO', 19),
(314, 23161024, 'CHELSICA', 19),
(315, 23161046, 'DARREN', 19),
(316, 23161014, 'ENGELINE CHAIRINE', 19),
(317, 23161063, 'IMMANUEL RAJU GINTING', 19),
(318, 23161059, 'JESEN GAUTAMA', 19),
(319, 23161065, 'JESYCA', 19),
(320, 23161018, 'KEVIN YAP', 19),
(321, 23161007, 'MEITA RONAVITA', 19),
(322, 23161036, 'RAY HANDRE SIMANULLANG', 19),
(323, 23161027, 'RICHMOND', 19),
(324, 23161038, 'STEVEN CHIN', 19),
(325, 23161039, 'BERBY CALTIANY', 16),
(326, 23161026, 'CELSIA', 16),
(327, 23161011, 'CINDI COESTARINATAN', 16),
(328, 23161040, 'DANIEL CALVANDO', 16),
(329, 23161060, 'ELISABETH DEI BELUTOWE', 16),
(330, 23161052, 'HEINRY LIM', 16),
(331, 21161066, 'INTAN NURAINI HARAHAP', 16),
(332, 23161009, 'JESSICA', 16),
(333, 23161035, 'KEISYA AURELIA PUTRI JESYA', 16),
(334, 23161030, 'LUZY CLARA NOVELLE', 16),
(335, 23161047, 'MEILYA', 16),
(336, 23161037, 'QUEENA MICHAELLA MISUJA', 16),
(337, 23161049, 'RIANTY PANG', 16),
(338, 23161042, 'ROSALINDA', 16),
(339, 23161033, 'SHEILA', 16),
(340, 23161041, 'SHERRY', 16),
(341, 23161012, 'SUYANA', 16),
(342, 23161020, 'VIOLA VIOLETTA', 16),
(343, 24161061, 'AGUSTINUS', 17),
(344, 23161005, 'ANGEL TORNANDUS', 17),
(345, 23161043, 'ANISA IMRAN', 17),
(346, 23161001, 'ARYANI FANIRA', 17),
(347, 23161021, 'CHRISTINE ANGELLYA', 17),
(348, 23161003, 'DISKA RIZKITHA', 17),
(349, 23161002, 'EVELYN LIM', 17),
(350, 23161051, 'FELICIA AGATHA YANG', 17),
(351, 23161055, 'JOCELIN', 17),
(352, 23161015, 'JUNATAN', 17),
(353, 23161058, 'MARIANI', 17),
(354, 23161019, 'REMON SAPUTRA', 17),
(355, 23161034, 'STEVANI', 17),
(356, 23161010, 'WINY', 17);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor`
--

CREATE TABLE `supervisor` (
  `supervisorid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor`
--

INSERT INTO `supervisor` (`supervisorid`, `username`, `name`, `email`, `password`) VALUES
(1, 'admin', 'admin', 'admin@gmail.com', '$2y$12$2grVXLLJ4K1Y5sKH37ELSuYg2UFLwKAG85jU7u8plTYbhAIEPu/Gy'),
(2, '121206144', 'Srima Deliana damanik', '121206144@gmail.com', '$2y$12$9kGO.BcrPNcorBOOrRlrHOfGmU9Ij4wWn5d5WP/VKlNawvf.SGHbC'),
(3, '110706010', 'Martha Hutauruk', '110706010@gmail.com', '$2y$12$usICrWcwTO.bn9EsaDAwwe5cVd4tHCseTmEQ0cMRRU5XOmUdKpWSa'),
(4, '111202151', 'Melda Triani Samosir', '111202151@gmail.com', '$2y$12$13nHm3uxNB7dDJ6PNLBceezFnZhZwFrzEewOLoR/GCErydZKyw5jm'),
(5, '111207165', 'Elvi Juita Saragih', '111207165@gmail.com', '$2y$12$8jNmjT33LG6fp78q0VhdHeuADnmJz2pAWD.WGPZSHRbYdX2Evnkw6'),
(6, '111207169', 'Meiana Sulastri Siregar', '111207169@gmail.com', '$2y$12$VB1LKxaFELRycge1eAfvv.bQ3WODgGdw9wr0Mn.RpNKaAdKaLBS7.'),
(7, '111409118', 'Rais Zulwardan', '111409118@gmail.com', 'b1b697761ef8f232191299af1fa47a2ab01bf3792634d9751fe242c44ace0dbf'),
(8, '111508178', 'Nofrinda Tri Loli Sitohang', '111508178@gmail.com', 'f0cb7ae969bfb20dd5bc28638a35ad1c2004098fcec015dc175f9094e106e146'),
(9, '111507177', 'Marni Feronika', '111507177@gmail.com', 'c75298b0a10afb15822a1836a1aad725a8ef8845ac5e20b52baee85d0404c2ec'),
(10, '111511204', 'Rina Samosir', '111511204@gmail.com', '0ef45c61b3641c048373e418b156176d68c261ea6f1c8e917f809d6b49a7298f'),
(11, '111603221', 'Miftahul Ilmi', '111603221@gmail.com', 'dba3091e58169867e26b1adb16f298acdb356bf64fee1b502ec01c84c2707d58'),
(12, '111809309', 'Pranses Simanullang', '111809309@gmail.com', '8f0f131cf6018a2fd68f0dd6090aeb25fb861f8595b8588b989b1c018bf514f2'),
(13, '112001327', 'Tuti Mardiati', '112001327@gmail.com', '82a273671891c74e616f70b62e41691a52203ff12a69b252793e229cf863f33f'),
(14, '112107356', 'Bima Yefri Fauzi', '112107356@gmail.com', '9767524957122924f2bed938287c9c3241c7b3ba35c19470b33905449143b2ee'),
(15, '112108359', 'Dedi Prianto Ginting', '112108359@gmail.com', '0f7f9dc45706e5857be110305f216e33adb799251be520adfb57956ba879d273'),
(16, '112207371', 'Julia Mutiara Rizkosa', '112207371@gmail.com', '76353314d092a02f4c1811756aff047af6a739e03a71f5cdcfa844fc07f82991'),
(17, '112207377', 'Heni Safitri', '112207377@gmail.com', '563782e6992131ed0eba9d27720e034d47a8c957fd9692e0841287a948ae9677'),
(18, '112207378', 'Desi Nataliza Br', '112207378@gmail.com', 'b607f0ba29b1a03b46d389c0713b403ecb01f8b516b6e373cdeace68c54102ba'),
(19, '112210384', 'Supri Rahman', '112210384@gmail.com', '26b9f4e80371ccaba39109a46b7f1b6b862761c334ee7e5115f060f51042cb31'),
(20, '112302389', 'Yosepha Agustine', '112302389@gmail.com', '21a0df3b22059c094457ce2c9ec588a0f399d8abf0e0ffebfeb70e8e7dc5e55e'),
(21, '112210383', 'Restu Laia', '112210383@gmail.com', '17bcb3157c9fdc4e0e81ec80a37947202ce5a7d1f1cce7f071ba3f866ea9f5be'),
(22, '112307393', 'Beni Yusman Lase', '112307393@gmail.com', 'a1b4a196b0db5a0077bbf3e6591e85a5ea1093b07698f4132d06118ad6700a8b'),
(23, '112307394', 'Eka Trisetya Wulandari', '112307394@gmail.com', '42da642714378a2e82f3986016ef2018ce14aaebe7718fec82fb1dbb1b199328'),
(24, '112307395', 'Afdal Idul Fitra', '112307395@gmail.com', 'fbe44ba4af93ace9c41e19d21bb9bab30f5677754b613603aa4162af58ca6894'),
(25, '112307396', 'Rosita Elisabet Surbakti', '112307396@gmail.com', 'b81d743fdbce1509d526cea1565ac84f2892f62a592d992b5bdb090d97038635'),
(26, '112307399', 'Rivi Andri', '112307399@gmail.com', 'deecdf69144041bc4534c4bece3691f5dd0f2f22309fbdcc1447ad65c977a6ac'),
(27, '112507451', 'Jefry Samosir', '112507451@gmail.com', 'cb391c7593f40c55859dc9f866175738b5ec1d00278cec536652496ed8c6558d'),
(28, '112507453', 'kesita', '112507453@gmail.com', 'd6c94a2e2536c0700f45f9d9dc3eb9df1b53cc99a0fa2ba4afad01ec3d4be905'),
(29, '112401414', 'Yudhianto', '112401414@gmail.com', 'a2e5bcc14ade727ad74e31f877ffae4fed45cf23a785df882ad48d57cc654e3d'),
(30, '112507454', 'Mita Afrianti', '112507454@gmail.com', '49698a39a628ea5a39c4270a83a12f662583d34da5229b07eff7e74869695336'),
(31, '112408445', 'Cindy Astuti', '112408445@gmail.com', '1502ea8bc16b662d54342220600ea478bf1f68c03e833f6e0a565a0c2ae6ad72'),
(32, '112404419', 'Nofri Agnesita Sitanggang', '112404419@gmail.com', 'fb21832a3b8a70b1254dc9e3e55ad5713108c9c8f05ce32ff3ce82624795fbed'),
(33, '112408444', 'Irna Arissa Sitanggang', '112408444@gmail.com', '65682d3b09a44d9233eaa5ef79cfc37ddd629e5e4f6d0481a6e7c9c49b3980b4');

-- --------------------------------------------------------

--
-- Table structure for table `used_code`
--

CREATE TABLE `used_code` (
  `usedid` int(11) NOT NULL,
  `codeid` int(11) NOT NULL,
  `studentid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `used_code`
--

INSERT INTO `used_code` (`usedid`, `codeid`, `studentid`) VALUES
(11, 34, 1),
(15, 35, 273),
(16, 37, 273);

-- --------------------------------------------------------

--
-- Table structure for table `violation_logs`
--

CREATE TABLE `violation_logs` (
  `violationid` bigint(20) NOT NULL,
  `studentid` bigint(20) NOT NULL,
  `violation_detail` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `detected_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violation_logs`
--

INSERT INTO `violation_logs` (`violationid`, `studentid`, `violation_detail`, `description`, `detected_at`) VALUES
(1, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:34:57'),
(2, 7, 'User mencoba keluar/pindah aplikasi dari protected webview', 'raw_type=LEAVE_APP_ATTEMPT; detail=User mencoba keluar/pindah aplikasi dari protected webview', '2026-05-11 22:34:57'),
(3, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:34:57'),
(4, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:36:18'),
(5, 7, 'User mencoba keluar/pindah aplikasi dari protected webview', 'raw_type=LEAVE_APP_ATTEMPT; detail=User mencoba keluar/pindah aplikasi dari protected webview', '2026-05-11 22:36:18'),
(6, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:36:18'),
(7, 7, 'Tombol back diblokir di protected webview', 'raw_type=BACK_BLOCKED; detail=Tombol back diblokir di protected webview', '2026-05-11 22:36:43'),
(8, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:37:36'),
(9, 7, 'User mencoba keluar/pindah aplikasi dari protected webview', 'raw_type=LEAVE_APP_ATTEMPT; detail=User mencoba keluar/pindah aplikasi dari protected webview', '2026-05-11 22:37:36'),
(10, 7, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-11 22:37:36'),
(11, 273, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-12 01:39:18'),
(12, 273, 'User mencoba keluar/pindah aplikasi dari protected webview', 'raw_type=LEAVE_APP_ATTEMPT; detail=User mencoba keluar/pindah aplikasi dari protected webview', '2026-05-12 01:39:18'),
(13, 273, 'Aplikasi masuk background saat sesi ujian aktif', 'raw_type=APP_BACKGROUND; detail=Aplikasi masuk background saat sesi ujian aktif', '2026-05-12 01:39:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`classid`);

--
-- Indexes for table `exam_codes`
--
ALTER TABLE `exam_codes`
  ADD PRIMARY KEY (`codeid`),
  ADD UNIQUE KEY `entry_code` (`code`);

--
-- Indexes for table `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`gradeid`);

--
-- Indexes for table `major`
--
ALTER TABLE `major`
  ADD PRIMARY KEY (`majorid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studentid`);

--
-- Indexes for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD PRIMARY KEY (`supervisorid`);

--
-- Indexes for table `used_code`
--
ALTER TABLE `used_code`
  ADD PRIMARY KEY (`usedid`),
  ADD UNIQUE KEY `uniq_used_code_per_student` (`codeid`,`studentid`);

--
-- Indexes for table `violation_logs`
--
ALTER TABLE `violation_logs`
  ADD PRIMARY KEY (`violationid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `classid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `exam_codes`
--
ALTER TABLE `exam_codes`
  MODIFY `codeid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `grade`
--
ALTER TABLE `grade`
  MODIFY `gradeid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `major`
--
ALTER TABLE `major`
  MODIFY `majorid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `studentid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=357;

--
-- AUTO_INCREMENT for table `supervisor`
--
ALTER TABLE `supervisor`
  MODIFY `supervisorid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `used_code`
--
ALTER TABLE `used_code`
  MODIFY `usedid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `violation_logs`
--
ALTER TABLE `violation_logs`
  MODIFY `violationid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
