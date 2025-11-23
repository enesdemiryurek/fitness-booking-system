-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 23 Kas 2025, 15:31:56
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `fitness_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `booking_date`) VALUES
(3, 3, 1, '2025-11-19 17:27:11'),
(6, 2, 5, '2025-11-20 13:19:46'),
(7, 4, 5, '2025-11-20 13:45:55'),
(8, 4, 6, '2025-11-20 13:49:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `trainer_name` varchar(50) NOT NULL,
  `class_type` varchar(30) NOT NULL,
  `date_time` datetime NOT NULL,
  `capacity` int(11) NOT NULL,
  `video_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `classes`
--

INSERT INTO `classes` (`id`, `title`, `description`, `trainer_name`, `class_type`, `date_time`, `capacity`, `video_link`, `created_at`) VALUES
(1, 'Sabah Yogası', 'Güne zinde başlamak için harika bir ders.', 'Ayşe Hoca', 'Yoga', '2025-11-25 09:00:00', 9, 'https://zoom.us/j/123456', '2025-11-19 16:31:04'),
(2, 'Push Pull Legs', 'Push Pull legs dersi', 'Enes Hoca', 'Fitness', '2025-11-23 13:50:00', 10, 'https://support.zoom.com/hc/tr', '2025-11-20 09:49:55'),
(3, 'Pilates ', 'Pilatesin babası', 'Bilmem ne HOCA', 'Pilates', '2025-11-23 13:03:00', 15, 'https://support.zoom.com/hc/tr', '2025-11-20 10:04:07'),
(4, 'Zumba', 'zumbacı baba', 'Zumbacı', 'Zumba', '2025-11-03 13:09:00', 5, 'https://support.zoom.com/hc/tr', '2025-11-20 10:04:32'),
(5, 'kardiyonun babası', 'Yağları yakıyoz', 'zattırızot', 'HIIT', '2025-11-30 13:10:00', 98, 'https://support.zoom.com/hc/tr', '2025-11-20 10:05:07'),
(6, 'deneme', 'deneme', 'deneme', 'Yoga', '2025-11-20 16:50:00', 1, 'https://support.zoom.com/hc/tr', '2025-11-20 13:48:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `class_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 6, 5, 'kötü', '2025-11-20 13:56:50');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','instructor','admin') DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `age`, `gender`, `password`, `role`, `profile_pic`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', NULL, NULL, NULL, '123', 'admin', 'default.png', '2025-11-19 16:31:03'),
(2, 'ogrenci1', 'ogrenci1@gmail.com', NULL, NULL, NULL, '123', 'user', 'default.png', '2025-11-19 16:31:03'),
(3, 'enesdemiryürek', 'instructor@gmail.com', NULL, NULL, NULL, '123', 'instructor', 'default.png', '2025-11-19 17:09:40'),
(4, 'elif çalışkaner', 'elifbaba@gmail.com', '03483653535', 31, 'Kadın', '123', 'user', 'default.png', '2025-11-20 13:45:36'),
(5, 'Enes Demiryürek', 'enesdmryurek@gmail.com', '05433057026', 22, 'Erkek', 'Enesbaba', 'user', 'default.png', '2025-11-23 11:36:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` int(11) NOT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `record_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `weight`, `height`, `bmi`, `record_date`) VALUES
(1, 1, 80.00, 185, 23.37, '2025-11-20 11:38:27'),
(2, 1, 80.00, 185, 23.37, '2025-11-20 11:41:43'),
(3, 1, 80.00, 185, 23.37, '2025-11-20 11:41:45'),
(4, 1, 80.00, 185, 23.37, '2025-11-20 11:41:50'),
(5, 1, 80.00, 185, 23.37, '2025-11-20 11:41:51'),
(6, 1, 80.00, 185, 23.37, '2025-11-20 11:41:52'),
(7, 2, 80.00, 185, 23.37, '2025-11-20 11:43:40'),
(8, 4, 57.00, 173, 19.05, '2025-11-20 13:46:21');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Tablo için indeksler `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
