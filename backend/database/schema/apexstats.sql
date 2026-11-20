-- ============================================================
-- APEX STATS — MySQL schema + demo data
-- MySQL 8.0+ / MariaDB 10.6+
-- Import: mysql -u root -p < apexstats.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `apexstats`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `apexstats`;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `verification_requests`;
DROP TABLE IF EXISTS `match_records`;
DROP TABLE IF EXISTS `legend_stats`;
DROP TABLE IF EXISTS `player_profiles`;
DROP TABLE IF EXISTS `legends`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('player','admin') NOT NULL DEFAULT 'player',
  `steam_id` varchar(17) DEFAULT NULL,
  `steam_name` varchar(64) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_nickname_unique` (`nickname`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_steam_id_unique` (`steam_id`),
  KEY `users_role_index` (`role`),
  KEY `users_is_blocked_index` (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `legends` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(40) NOT NULL,
  `name` varchar(60) NOT NULL,
  `real_name` varchar(100) NOT NULL,
  `role` enum('assault','skirmisher','recon','support','controller') NOT NULL,
  `difficulty` tinyint NOT NULL DEFAULT 2,
  `tagline` varchar(180) DEFAULT NULL,
  `lore` text,
  `passive_name` varchar(100) DEFAULT NULL,
  `passive_desc` varchar(500) DEFAULT NULL,
  `tactical_name` varchar(100) DEFAULT NULL,
  `tactical_desc` varchar(500) DEFAULT NULL,
  `ultimate_name` varchar(100) DEFAULT NULL,
  `ultimate_desc` varchar(500) DEFAULT NULL,
  `forte` varchar(180) DEFAULT NULL,
  `tip` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `accent_color` varchar(7) NOT NULL DEFAULT '#ff4d3d',
  `pick_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `release_season` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legends_slug_unique` (`slug`),
  KEY `legends_role_index` (`role`),
  KEY `legends_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `player_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `steam_id` varchar(17) DEFAULT NULL,
  `level` int unsigned NOT NULL DEFAULT 1,
  `rank_tier` varchar(40) NOT NULL DEFAULT 'Бронза IV',
  `rank_points` int unsigned NOT NULL DEFAULT 0,
  `hours_played` int unsigned NOT NULL DEFAULT 0,
  `total_kills` int unsigned NOT NULL DEFAULT 0,
  `wins` int unsigned NOT NULL DEFAULT 0,
  `total_damage` bigint unsigned NOT NULL DEFAULT 0,
  `matches_played` int unsigned NOT NULL DEFAULT 0,
  `kd` decimal(5,2) NOT NULL DEFAULT 0.00,
  `winrate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `headshot_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `battle_pass` tinyint unsigned NOT NULL DEFAULT 0,
  `main_legend_id` bigint unsigned DEFAULT NULL,
  `country` char(2) NOT NULL DEFAULT 'RU',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_profiles_user_id_unique` (`user_id`),
  KEY `player_profiles_steam_id_index` (`steam_id`),
  KEY `player_profiles_rank_points_index` (`rank_points`),
  KEY `player_profiles_main_legend_id_foreign` (`main_legend_id`),
  CONSTRAINT `player_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_profiles_main_legend_id_foreign` FOREIGN KEY (`main_legend_id`) REFERENCES `legends` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `legend_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_profile_id` bigint unsigned NOT NULL,
  `legend_id` bigint unsigned NOT NULL,
  `matches` int unsigned NOT NULL DEFAULT 0,
  `kills` int unsigned NOT NULL DEFAULT 0,
  `wins` int unsigned NOT NULL DEFAULT 0,
  `damage` bigint unsigned NOT NULL DEFAULT 0,
  `playtime_hours` int unsigned NOT NULL DEFAULT 0,
  `best_season` varchar(40) DEFAULT NULL,
  `winrate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legend_stats_player_profile_id_legend_id_unique` (`player_profile_id`,`legend_id`),
  KEY `legend_stats_player_profile_id_kills_index` (`player_profile_id`,`kills`),
  CONSTRAINT `legend_stats_player_profile_id_foreign` FOREIGN KEY (`player_profile_id`) REFERENCES `player_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legend_stats_legend_id_foreign` FOREIGN KEY (`legend_id`) REFERENCES `legends` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `match_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_profile_id` bigint unsigned NOT NULL,
  `legend_id` bigint unsigned DEFAULT NULL,
  `map` varchar(60) NOT NULL,
  `mode` varchar(40) NOT NULL,
  `placement` tinyint unsigned NOT NULL,
  `kills` tinyint unsigned NOT NULL DEFAULT 0,
  `damage` int unsigned NOT NULL DEFAULT 0,
  `rp` smallint NOT NULL DEFAULT 0,
  `played_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `match_records_player_profile_id_played_at_index` (`player_profile_id`,`played_at`),
  KEY `match_records_played_at_index` (`played_at`),
  KEY `match_records_legend_id_foreign` (`legend_id`),
  CONSTRAINT `match_records_player_profile_id_foreign` FOREIGN KEY (`player_profile_id`) REFERENCES `player_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `match_records_legend_id_foreign` FOREIGN KEY (`legend_id`) REFERENCES `legends` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `verification_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(60) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `admin_comment` text,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_requests_status_index` (`status`),
  KEY `verification_requests_user_id_status_index` (`user_id`,`status`),
  KEY `verification_requests_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `verification_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verification_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DEMO DATA
-- ============================================================

INSERT INTO `users` (`id`, `nickname`, `email`, `password`, `role`, `steam_id`, `steam_name`, `avatar`, `is_blocked`, `last_login_at`, `created_at`, `updated_at`) VALUES
  (1, 'SectorAdmin', 'admin@apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$Goy6TSxmKZBokUcejNVbPQ$+Of7ssRl96cGrbOSa1CGymkDg1TsvzUeKTJ135LJiq8', 'admin', '76561198000000001', 'SectorAdmin', '/assets/avatars/admin.svg', 0, '2026-11-02 09:12:00', '2024-11-03 12:00:00', '2026-11-02 09:12:00'),
  (2, 'Voidwalker', 'player@apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198342109784', 'Voidwalker_07', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2025-02-14 12:00:00', '2026-11-02 09:12:00'),
  (3, 'Solar1s', 'solar1s@mail.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000003', 'Solar1s', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2025-04-01 12:00:00', '2026-11-02 09:12:00'),
  (4, 'Nesh', 'nesh.apex@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000004', 'NeshGG', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2025-05-19 12:00:00', '2026-11-02 09:12:00'),
  (5, 'Koyful', 'koyful@outlook.com', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000005', 'Koyful', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2025-06-07 12:00:00', '2026-11-02 09:12:00'),
  (6, 'RiftRunner', 'rift.runner@yandex.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000006', 'RiftRunner', '/assets/avatars/voidwalker.svg', 1, '2026-11-02 09:12:00', '2025-07-22 12:00:00', '2026-11-02 09:12:00'),
  (7, 'MirageMain', 'mirage.main@mail.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000007', 'Bamboozle', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2025-09-30 12:00:00', '2026-11-02 09:12:00'),
  (8, 'LobaLoot', 'loba.loot@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', '76561198000000008', 'LobaLoot', '/assets/avatars/voidwalker.svg', 0, '2026-11-02 09:12:00', '2026-01-11 12:00:00', '2026-11-02 09:12:00'),
  (9, 'ImperialHal', 'imperialhal@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'TSM · ImperialHal', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (10, 'HisWattson', 'hiswattson@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'FLCN · HisWattson', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (11, 'Genburten', 'genburten@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'FLCN · Genburten', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (12, 'Zer0', 'zer0@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'G2 · Zer0', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (13, 'Sweetdreams', 'sweetdreams@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'NRG · Sweetdreams', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (14, 'YukaF', 'yukaf@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'FNATIC · YukaF', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (15, 'Effect', 'effect@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'ALL · Effect', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (16, 'Ras', 'ras@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'TSM · Ras', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (17, 'Nafen', 'nafen@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'NRG · Nafen', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (18, 'Sikezz', 'sikezz@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'OXG · Sikezz', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (19, 'Hakis', 'hakis@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'ALL · Hakis', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00'),
  (20, 'Mande', 'mande@leaderboard.apexstats.ru', '$argon2id$v=19$m=65536,t=4,p=1$BliwuV/zkaMxilC22Bl4Uw$KI14h6K9ArcpTDi5FcfDosjITpRfjbVr262jtz9HlsU', 'player', NULL, 'Mande', '/assets/avatars/voidwalker.svg', 0, '2026-11-01 18:40:00', '2025-08-14 12:00:00', '2026-11-02 09:12:00');

INSERT INTO `legends` (`id`, `slug`, `name`, `real_name`, `role`, `difficulty`, `tagline`, `lore`, `passive_name`, `passive_desc`, `tactical_name`, `tactical_desc`, `ultimate_name`, `ultimate_desc`, `forte`, `tip`, `image`, `accent_color`, `pick_rate`, `release_season`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'wraith', 'Wraith', 'Рен Блэйзи', 'skirmisher', 2, 'Между измерениями нет правил.', 'Лабораторный эксперимент стёр её прошлое, но оставил голоса из разлома. Рен научилась исчезать раньше, чем противник успевает нажать на курок, и теперь ищет ответы по ту сторону реальности.', 'Голос из пустоты', 'Предупреждает об опасности: враг, прицелившись, сообщает о себе, а рядом появится предупреждение о гранатах и ловушках.', 'В разлом', 'Быстрый рывок в пустоту: неуязвимость на 0,4 секунды и ускорение при возвращении.', 'Портальный разлом', 'Создаёт портал между двумя точками, через который вся команда может безопасно сменить позицию.', 'Заход с фланга и мгновенный выход из боя', 'Ставьте портал до начала перестрелки. Выход из ульты — это не побег, а новая точка атаки.', '/assets/legends/wraith.webp', '#8f7bff', 12.4, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (2, 'octane', 'Octane', 'Октавио Сильва', 'skirmisher', 1, 'Скорость — это единственная религия.', 'Сын богатейшего человека Солнечной системы променял наследство на адреналин. После того как он потерял ноги в очередной выходке, Октавио поставил себе механические протезы и стал ещё быстрее.', 'Стимулятор', 'Восстанавливает здоровье со временем, когда Октана не получает урон.', 'Стимулятор', 'Впрыск адреналина: +30% к скорости на 6 секунд ценой здоровья.', 'Пусковая площадка', 'Устанавливает батут, подбрасывающий союзников и врагов высоко в воздух.', 'Агрессивный раш и быстрый отход', 'Не тратьте стимулятор на пустой бег — здоровье пригодится для повторного входа в бой.', '/assets/legends/octane.webp', '#2ee66b', 11.8, 'Сезон 1', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (3, 'pathfinder', 'Pathfinder', 'MRVN', 'skirmisher', 2, 'Привет, друг! Готов к приключениям?', 'Дружелюбный робот MRVN ищет своего создателя. Пока поиски не закончены, Патфайндер с радостью поможет любой команде добраться до высоких точек — и попутно со всеми познакомится.', 'Внутренний компас', 'Сканирует маяки наблюдения и показывает следующее кольцо.', 'Крюк-кошка', 'Зацеп за поверхность и быстрое притяжение к точке.', 'Зиплайн', 'Прокладывает канат для всей команды между двумя точками.', 'Вертикальные перемещения и занятие высот', 'Крюк работает как маятник: зацепитесь сбоку и отпустите в высшей точке дуги.', '/assets/legends/pathfinder.webp', '#3fa9f5', 9.7, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (4, 'lifeline', 'Lifeline', 'Аджей Че', 'support', 1, 'Никто не остаётся позади.', 'Наследница фармацевтической империи выбрала поле боя вместо совета директоров. Аджей верит: помощь должна приходить вовремя, а не после подписания контракта.', 'Боевой медик', 'Поднимает союзников быстрее и под прикрытием Drone DOC.', 'Дрон DOC', 'Дрон лечит союзников и восстанавливает щиты рядом с собой.', 'Кейс поддержки', 'Вызывает капсулу с улучшенным щитом и снаряжением для команды.', 'Реанимация под огнём и затяжные бои', 'Ставьте DOC за укрытием: дрон лечит даже тогда, когда вы ведёте огонь.', '/assets/legends/lifeline.webp', '#ff6f91', 8.2, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (5, 'bloodhound', 'Bloodhound', 'Бладхаунд', 'recon', 2, 'Боги не видят. Мы видим.', 'Охотник с ледяных равнин пришёл на Игры, чтобы доказать: технологии не заменят чутьё. Бладхаунд читает следы так же легко, как другие читают заголовки.', 'Взгляд охотника', 'Оставляет следы врагов, которые недавно проходили рядом, и видит их подсказки.', 'Взгляд всеотца', 'Короткий скан, раскрывающий врагов, ловушки и следы сквозь стены.', 'Охота зверя', 'Усиливает восприятие, скорость и подсвечивает добычу на всей арене.', 'Инициация боя и поиск третьей стороны', 'Не сканируйте пустую комнату. Лучше дождитесь звука шагов и зажмите угол.', '/assets/legends/bloodhound.webp', '#ff7a3d', 7.6, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (6, 'bangalore', 'Bangalore', 'Анита Уильямс', 'assault', 2, 'Дым — это тоже оружие.', 'Профессиональный военный, которая привыкла выполнять приказ без лишних вопросов. На Играх Анита впервые задаёт вопрос: кому на самом деле выгодна эта война?', 'Ускорение под огнём', 'Ускоряется, когда по ней ведут огонь.', 'Дымовая завеса', 'Две дымовые гранаты для укрытия и смены позиции.', 'Ковровая бомбардировка', 'Артиллерийский обстрел по площади с задержкой детонации.', 'Контроль пространства и спасение раненых', 'Дым спасает не только вас: он режет обзор снайперам, которые держат вашу команду.', '/assets/legends/bangalore.webp', '#c9a227', 6.4, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (7, 'gibraltar', 'Gibraltar', 'Макеле Тупуола', 'support', 2, 'Большой щит — большая ответственность.', 'Спасатель из Солнечного города, который прикрывает чужие спины лучше, чем собственную. Его купол — последняя линия обороны, когда кольцо уже не оставляет вариантов.', 'Щит бастиона', 'При прицеливании раскрывает личный энергетический щит.', 'Купол защиты', 'Ставит купол на 15 секунд, блокирующий входящий урон.', 'Оборонительный обстрел', 'Наносит артиллерийский удар по указанной зоне.', 'Долгие бои в укрытии и поддержка команды', 'Купол можно поставить на союзника, чтобы поднять его под обстрелом.', '/assets/legends/gibraltar.webp', '#f2994a', 4.9, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (8, 'caustic', 'Caustic', 'Александр Нокс', 'controller', 3, 'Наука не знает жалости.', 'Бывший учёный, который проверяет гипотезы на живых противниках. Его газ — не оружие, а инструмент: он превращает любую комнату в лабораторию.', 'Газовый ловец', 'Видит врагов в собственном газе и не получает от него урон.', 'Газовый ловушка', 'Ставит бочку, которая выпускает едкий газ при попадании.', 'Газовый гранат', 'Создаёт облако концентрированного газа по площади.', 'Оборона зданий и узких проходов', 'Ставьте бочки у двух выходов: противник войдёт в один и попадёт в газ из другого.', '/assets/legends/caustic.webp', '#7bd88f', 3.7, 'Сезон 0', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (9, 'mirage', 'Mirage', 'Эллиот Уитт', 'support', 2, 'Обаяние — тоже тактика.', 'Голограф-шоумен, который прячет неуверенность за бесконечными шутками. Его клоны обманывают не только врагов, но иногда и самого Эллиота.', 'Теперь вы меня видите', 'При возрождении и использовании ульты становится невидимым и создаёт копию.', 'Психо-обман', 'Отправляет голограмму вперёд, чтобы отвлечь врага.', 'Фестиваль трюков', 'Выпускает отряд клонов и делает Мираж невидимым.', 'Хаос, обман и неожиданные реанимации', 'Управляйте клоном вручную: живая траектория убедит врага лучше прямой линии.', '/assets/legends/mirage.webp', '#ffc857', 4.1, 'Сезон 1', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (10, 'wattson', 'Wattson', 'Натали Пакетт', 'controller', 2, 'Дом там, где стоит забор.', 'Дочь главного инженера Игр выросла среди электрических ограждений. Для Натали безопасность — это точная схема, а не удача.', 'Спарка', 'Заряжает щиты союзников рядом с интерцептором.', 'Периметр', 'Ставит электрические узлы, соединяющиеся в забор.', 'Ограждение', 'Создаёт купол, уничтожающий гранаты и восстанавливающий щиты.', 'Удержание позиции и защита от гранат', 'Ставьте ульту заранее: купол снимает урон от газовых и термитных гранат.', '/assets/legends/wattson.webp', '#4ecdc4', 3.9, 'Сезон 2', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (11, 'crypto', 'Crypto', 'Тэ Джун Пак', 'recon', 3, 'Информация дороже пуль.', 'Хакер, который уничтожил собственную семью, чтобы остановить корпорацию. Он не доверяет никому — и поэтому видит поле боя лучше всех.', 'Нейросвязь', 'Все враги, обнаруженные дроном, подсвечиваются команде.', 'Дрон-наблюдатель', 'Запускает дрон для разведки, сканирования и взлома.', 'Орбитальная ЭМИ', 'Взрывает щиты и обездвиживает врагов в зоне действия.', 'Разведка, взлом и подготовка атаки', 'Дрон — это камера, а не оружие. Прячьте его за укрытием и сканируйте маяки.', '/assets/legends/crypto.webp', '#25c1d6', 3.2, 'Сезон 3', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (12, 'revenant', 'Revenant', 'Ревенант', 'assault', 2, 'Смерть — это просто ещё одна смена.', 'Убийца, которого превратили в бессмертную машину и заставили забыть об этом. Теперь он помнит всё — и не собирается прощать.', 'Убийца', 'Лазает по стенам выше и двигается быстрее при приседе.', 'Тень', 'Бросок сферы, блокирующей способности врагов.', 'Тотем смерти', 'Даёт команде временную защиту от смерти.', 'Агрессивные заходы без страха умереть', 'Тотем ставьте за стеной: враг должен услышать его, но не видеть.', '/assets/legends/revenant.webp', '#d94f4f', 5.6, 'Сезон 4', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (13, 'loba', 'Loba', 'Лоба Андрейде', 'support', 2, 'Всё, что блестит, будет моим.', 'Воровка, которая охотится не за богатством, а за местью. Её черный рынок открывается там, где команде нужнее всего второй шанс.', 'Глаз бури', 'Видит добычу сквозь стены и может перехватывать предметы.', 'Браслет прыгуна', 'Бросает браслет и телепортируется к нему.', 'Чёрный рынок', 'Открывает магазин с предметами для команды.', 'Быстрый лут и мобильность', 'Браслет не бросайте в стену — лучше на крышу, к которой враг не готовит прицел.', '/assets/legends/loba.webp', '#c084fc', 6.9, 'Сезон 5', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (14, 'rampart', 'Rampart', 'Рамия Парем', 'controller', 1, 'Хорошая пушка решает всё.', 'Инженер-самоучка из Гейи, которая собрала собственную мастерскую из мусора. Её Шила не спрашивает, сколько у врага щитов.', 'Модифицированный', 'Увеличенный магазин и ускоренная перезарядка пулемётов.', 'Укреплённая стена', 'Ставит усиленное укрытие с огневой амбразурой.', 'Пулемёт Шила', 'Разворачивает стационарный пулемёт с огромным уроном.', 'Оборона и подавление огнём', 'Стена усиливает ваш урон. Ставьте её до начала перестрелки, а не под пулями.', '/assets/legends/rampart.webp', '#f2a900', 4.3, 'Сезон 6', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (15, 'horizon', 'Horizon', 'Мэри Сомерс', 'skirmisher', 3, 'Гравитация — вопрос договорённости.', 'Астрофизик, которая потеряла сына в чёрной дыре и теперь ищет способ вернуть время назад. На арене её уравнения работают безотказно.', 'Космическая хватка', 'Более точное прицеливание в воздухе и быстрое приземление.', 'Гравитационный лифт', 'Поднимает союзников и врагов в воздух.', 'Чёрная дыра', 'Стягивает врагов в точку и оглушает их.', 'Высоты и вертикальные дуэли', 'Лифт можно использовать как укрытие: враг на земле теряет угол.', '/assets/legends/horizon.webp', '#7dd3fc', 8.7, 'Сезон 7', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (16, 'fuse', 'Fuse', 'Уолтер Фицрой', 'assault', 2, 'Взрывы — это язык любви.', 'Ветеран подпольных боёв, который пришёл на Игры за настоящим адреналином. Уолтер считает, что гранат много не бывает — и обычно прав.', 'Гренадер', 'Носит больше гранат и бросает их дальше.', 'Кластерная бомба', 'Залп из двух гранат с разделением на осколки.', 'Мать всех бомб', 'Наносит удар по площади и поджигает врагов.', 'Зонирование и выкуривание из укрытий', 'Сначала бросайте ульту, затем тактическую — так враг не успеет выскочить.', '/assets/legends/fuse.webp', '#f6ad55', 4.7, 'Сезон 8', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (17, 'valkyrie', 'Valkyrie', 'Киото Ямагучи', 'skirmisher', 2, 'Небо принадлежит тем, кто не боится высоты.', 'Дочь пилота, которая выросла на историях о лучшем асе фронта. Киото пилотирует так, будто её жизнь — это продолжение воздушного боя.', 'Реактивный ранец', 'Позволяет летать и зависать в воздухе.', 'Ракетный залп', 'Выпускает ракеты, наносящие урон и сбивающие щиты.', 'Небесный прыжок', 'Поднимает команду в воздух и переносит к новой точке.', 'Быстрая ротация и вертикальный бой', 'Топливо кончается быстро. Планируйте посадку до того, как враг возьмёт вас в прицел.', '/assets/legends/valkyrie.webp', '#f472b6', 3.8, 'Сезон 9', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (18, 'seer', 'Seer', 'Обэй Эдола', 'recon', 3, 'Я вижу то, что вы прячете.', 'Мистик, который превратил врождённую чувствительность к энергии в искусство охоты. Обэй видит сердцебиение врага за бетонной стеной.', 'Сердцебиение', 'При прицеливании показывает направление на ближайших врагов.', 'Фокус внимания', 'Создаёт зону, раскрывающую и замедляющую врагов.', 'Выставка', 'Показывает врагов в большом радиусе в реальном времени.', 'Разведка перед атакой', 'Сканируйте перед входом, но не задерживайтесь: враг знает, что вы смотрите.', '/assets/legends/seer.webp', '#a78bfa', 2.9, 'Сезон 10', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (19, 'ash', 'Ash', 'Эш', 'assault', 3, 'Я не проигрываю. Я заканчиваю.', 'Бывшая вершина Арены, чьё тело собрано из частей прошлого. Эш помнит каждую свою победу — и каждое поражение, которое ещё предстоит отомстить.', 'Разрыв', 'Отмечает место смерти противника и телепортируется к нему.', 'Дуговой разлом', 'Создаёт разлом, связывающий позиции.', 'Фазовый разлом', 'Открывает портал для команды и задерживает врагов.', 'Мобильность и добивание', 'Разлом — это ловушка. Оставляйте его у выхода, чтобы отрезать отступление.', '/assets/legends/ash.webp', '#ef4444', 5.2, 'Сезон 11', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (20, 'maggie', 'Mad Maggie', 'Маргарет Кохир', 'assault', 2, 'Правила придумали слабаки.', 'Лидер банды Салво, которая не признаёт ни власти, ни авторитетов. Маргарет идёт напрямую — и ожидает того же от всех остальных.', 'Военный трофей', 'Видит врагов, которых ранила, сквозь стены.', 'Буровая', 'Пускает снаряд, пробивающий укрытия и поджигающий врагов.', 'Крушитель', 'Запускает шар, ломающий щиты и замедляющий врагов.', 'Пробитие обороны', 'Буровая пробивает стены. Не тратьте её на открытую местность.', '/assets/legends/maggie.webp', '#fb7185', 4.4, 'Сезон 12', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (21, 'newcastle', 'Newcastle', 'Джексон Уильямс', 'support', 2, 'Я держу строй.', 'Бывший солдат, который вернулся из тени, чтобы защитить тех, кого любит. Его щит — не просто металл, а обещание: пока он стоит, команда не останется одна.', 'Щит возмездия', 'Поднимает союзников под прикрытием переносного щита.', 'Подвижный щит', 'Ставит передвижное укрытие с энергетической защитой.', 'Замок', 'Строит укреплённый комплекс с щитом и турелью.', 'Спасение под огнём и удержание точки', 'Щит можно двигать. Прикрывайте отступление, а не только вход.', '/assets/legends/newcastle.webp', '#60a5fa', 5.9, 'Сезон 13', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (22, 'vantage', 'Vantage', 'Марла Контрерас', 'recon', 3, 'Смотри шире. Бей дальше.', 'Снайпер, выросшая на ледяной планете с одним наставником и одним правилом: наблюдать до того, как действовать. Марла видит поле боя как карту.', 'Наблюдатель', 'Определяет дальность и тип брони цели в прицеле.', 'Эхо-локатор', 'Запускает летающего напарника Эхо для разведки.', 'Снайперская винтовка', 'Достаёт мощную винтовку с отметкой цели.', 'Дальний бой и разведка', 'Эхо можно переставлять. Держите его над укрытием, а сами меняйте позицию.', '/assets/legends/vantage.webp', '#38bdf8', 2.6, 'Сезон 14', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (23, 'catalyst', 'Catalyst', 'Тресс Фрейзер', 'controller', 2, 'Камень помнит всё.', 'Архитектор, которая строила города, а теперь перестраивает поле боя. Тресс умеет превращать пространство в крепость за считанные секунды.', 'Барьер', 'Усиливает и восстанавливает разрушаемые укрытия.', 'Шипы', 'Создаёт шипы, замедляющие и ранящие врагов.', 'Железный занавес', 'Поднимает стену, разделяющую поле боя.', 'Контроль пространства и крепости', 'Стена режет обзор, но и ваш тоже. Оставляйте проход для союзников.', '/assets/legends/catalyst.webp', '#c084fc', 3.4, 'Сезон 15', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (24, 'ballistic', 'Ballistic', 'Огастес Бринкман', 'assault', 2, 'Старая школа ещё в деле.', 'Легенда довоенных времён, который вернулся на арену, чтобы доказать: опыт не устаревает. Его оружие знает о нём больше, чем он сам.', 'Шаблон', 'Ускоряет перезарядку и улучшает оружие при подборе.', 'Взрывная волна', 'Выпускает волну, сбивающую врагов с ног.', 'Оружейный ящик', 'Улучшает оружие всей команды на время.', 'Поддержка огнём и контроль темпа', 'Ульта усиливает всю команду. Используйте её перед штурмом, а не после.', '/assets/legends/ballistic.webp', '#fbbf24', 3.1, 'Сезон 17', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00'),
  (25, 'conduit', 'Conduit', 'Роуни Тан', 'support', 1, 'Твой щит — моя благодарность.', 'Молодая механик, которая выжила благодаря легенде и теперь возвращает долг каждому союзнику. Её энергия — не магия, а наука и немного упрямства.', 'Доброволец', 'Видит щиты союзников и ускоряется к ним.', 'Щит', 'Передаёт союзнику заряд щита на расстоянии.', 'Прилив', 'Создаёт зону, дающую щиты и ускорение команде.', 'Поддержка в разгар боя', 'Щит можно передать сквозь стену. Следите за полосой союзника и не ждите его просьбы.', '/assets/legends/conduit.webp', '#34d399', 4.8, 'Сезон 19', 1, '2026-01-01 12:00:00', '2026-11-02 09:00:00');

INSERT INTO `player_profiles` (`id`, `user_id`, `steam_id`, `level`, `rank_tier`, `rank_points`, `hours_played`, `total_kills`, `wins`, `total_damage`, `matches_played`, `kd`, `winrate`, `headshot_rate`, `battle_pass`, `main_legend_id`, `country`, `last_synced_at`, `created_at`, `updated_at`) VALUES
  (2, 1, NULL, 610, 'Мастер', 18200, 3420, 52100, 2310, 12400000, 15400, 3.4, 15, 31.2, 110, 11, 'RU', '2026-10-31 19:20:00', '2024-11-03 12:00:00', '2026-11-02 09:12:00'),
  (1, 2, '76561198342109784', 487, 'Алмаз II', 8640, 1248, 18432, 624, 4812900, 6480, 2.84, 9.6, 27.4, 78, 1, 'RU', '2026-11-02 09:42:00', '2025-02-14 12:00:00', '2026-11-02 09:42:00'),
  (4, 3, NULL, 488, 'Алмаз II', 10870, 2940, 68120, 3241, 17574960, 15288, 3.36, 21.2, 28, 110, 17, 'RU', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (5, 4, NULL, 471, 'Алмаз II', 10240, 2810, 64760, 3039, 16708080, 14612, 3.28, 20.8, 29, 110, 13, 'BR', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (6, 5, NULL, 455, 'Алмаз III', 9680, 2680, 61340, 2801, 15825720, 13936, 3.19, 20.1, 30, 110, 16, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (7, 6, NULL, 184, 'Платина I', 5410, 684, 4260, 238, 1120000, 1840, 2.1, 12.9, 24.6, 54, 2, 'RU', '2026-10-31 19:20:00', '2025-07-22 12:00:00', '2026-11-02 09:12:00'),
  (8, 7, NULL, 184, 'Платина I', 5410, 684, 4260, 238, 1120000, 1840, 2.1, 12.9, 24.6, 54, 2, 'RU', '2026-10-31 19:20:00', '2025-09-30 12:00:00', '2026-11-02 09:12:00'),
  (9, 8, NULL, 184, 'Платина I', 5410, 684, 4260, 238, 1120000, 1840, 2.1, 12.9, 24.6, 54, 2, 'RU', '2026-10-31 19:20:00', '2026-01-11 12:00:00', '2026-11-02 09:12:00'),
  (10, 9, NULL, 973, 'Хищник', 24870, 6820, 148320, 12341, 38266560, 35464, 5.12, 34.8, 25, 110, 6, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (11, 10, NULL, 863, 'Хищник', 24110, 5940, 132760, 9915, 34252080, 30888, 4.88, 32.1, 26, 110, 10, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (12, 11, NULL, 980, 'Хищник', 23740, 7210, 154200, 11847, 39783600, 37492, 4.71, 31.6, 27, 110, 1, 'AU', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (13, 12, NULL, 765, 'Хищник', 22980, 5160, 118940, 7996, 30686520, 26832, 4.62, 29.8, 28, 110, 3, 'GB', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (14, 13, NULL, 805, 'Хищник', 22140, 5480, 124300, 8093, 32069400, 28496, 4.41, 28.4, 29, 110, 2, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (15, 14, NULL, 640, 'Мастер', 18640, 4160, 98240, 5668, 25345920, 21632, 4.12, 26.2, 30, 110, 15, 'JP', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (16, 15, NULL, 618, 'Мастер', 18110, 3980, 94180, 5319, 24298440, 20696, 3.98, 25.7, 31, 110, 19, 'KR', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (17, 16, NULL, 600, 'Мастер', 17860, 3840, 91260, 5012, 23545080, 19968, 3.86, 25.1, 32, 110, 12, 'DE', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (18, 17, NULL, 573, 'Мастер', 17240, 3620, 87400, 4593, 22549200, 18824, 3.74, 24.4, 24, 110, 21, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (19, 18, NULL, 559, 'Мастер', 16880, 3510, 84920, 4362, 21909360, 18252, 3.69, 23.9, 25, 110, 4, 'US', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (20, 19, NULL, 510, 'Алмаз I', 12640, 3120, 74180, 3667, 19138440, 16224, 3.51, 22.6, 26, 110, 23, 'DE', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00'),
  (21, 20, NULL, 678, 'Алмаз I', 12110, 4460, 86240, 5079, 22249920, 23192, 3.42, 21.9, 27, 110, 5, 'DE', '2026-11-02 08:30:00', '2025-08-14 12:00:00', '2026-11-02 08:30:00');

INSERT INTO `legend_stats` (`id`, `player_profile_id`, `legend_id`, `matches`, `kills`, `wins`, `damage`, `playtime_hours`, `best_season`, `winrate`, `created_at`, `updated_at`) VALUES
  (1, 1, 1, 1642, 4820, 318, 1284000, 402, 'Сезон 19', 19.4, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (2, 1, 2, 1518, 3910, 246, 1102000, 338, 'Сезон 16', 16.2, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (3, 1, 3, 1120, 2870, 204, 860400, 246, 'Сезон 8', 18.2, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (4, 1, 15, 760, 1760, 142, 520100, 164, 'Сезон 18', 18.7, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (5, 1, 12, 640, 1690, 121, 500700, 141, 'Сезон 14', 18.9, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (6, 1, 6, 820, 1980, 134, 580300, 178, 'Сезон 9', 16.3, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (7, 1, 13, 710, 1420, 138, 420300, 152, 'Сезон 17', 19.4, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (8, 1, 9, 690, 1560, 108, 460800, 147, 'Сезон 10', 15.6, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (9, 1, 4, 980, 1640, 198, 510900, 208, 'Сезон 12', 20.2, '2026-10-01 12:00:00', '2026-11-02 09:42:00'),
  (10, 1, 5, 890, 2140, 152, 640200, 192, 'Сезон 6', 17.1, '2026-10-01 12:00:00', '2026-11-02 09:42:00');

INSERT INTO `match_records` (`id`, `player_profile_id`, `legend_id`, `map`, `mode`, `placement`, `kills`, `damage`, `rp`, `played_at`, `created_at`, `updated_at`) VALUES
  (1, 1, 1, 'Осколок мира', 'Рейтинговый', 1, 9, 2870, 186, '2026-11-02 00:34:00', '2026-11-02 00:34:00', '2026-11-02 00:34:00'),
  (2, 1, 2, 'Край мира', 'Рейтинговый', 3, 6, 1940, 74, '2026-11-01 00:28:00', '2026-11-01 00:28:00', '2026-11-01 00:28:00'),
  (3, 1, 1, 'Олимпийский', 'Рейтинговый', 1, 7, 2210, 164, '2026-11-01 00:31:00', '2026-11-01 00:31:00', '2026-11-01 00:31:00'),
  (4, 1, 3, 'Край мира', 'Рейтинговый', 5, 4, 1460, 28, '2026-10-31 00:26:00', '2026-10-31 00:26:00', '2026-10-31 00:26:00'),
  (5, 1, 15, 'Олимп', 'Быстрый бой', 2, 8, 2130, 0, '2026-10-31 00:19:00', '2026-10-31 00:19:00', '2026-10-31 00:19:00'),
  (6, 1, 1, 'Каньон Кингс', 'Рейтинговый', 1, 11, 3240, 212, '2026-10-30 00:36:00', '2026-10-30 00:36:00', '2026-10-30 00:36:00'),
  (7, 1, 4, 'Осколок мира', 'Рейтинговый', 8, 2, 860, -24, '2026-10-30 00:22:00', '2026-10-30 00:22:00', '2026-10-30 00:22:00'),
  (8, 1, 2, 'Олимп', 'Рейтинговый', 3, 5, 1710, 61, '2026-10-29 00:27:00', '2026-10-29 00:27:00', '2026-10-29 00:27:00');

INSERT INTO `verification_requests` (`id`, `user_id`, `type`, `message`, `status`, `priority`, `admin_comment`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
  (1047, 3, 'Подтверждение ранга', 'Прошу подтвердить статус «Мастер» и привязать аккаунт к рейтингу сезона 27.', 'pending', 'high', NULL, NULL, NULL, '2026-11-01 10:00:00', '2026-11-01 10:00:00'),
  (1046, 4, 'Привязка Steam', 'Не отображаются часы игры. Steam ID: 76561198044219810.', 'pending', 'normal', NULL, NULL, NULL, '2026-11-01 10:00:00', '2026-11-01 10:00:00'),
  (1045, 7, 'Исправление статистики', 'В профиле пропали 3 сезона и статистика Mirage за сезон 22.', 'pending', 'normal', NULL, NULL, NULL, '2026-11-01 10:00:00', '2026-11-01 10:00:00'),
  (1044, 5, 'Восстановление доступа', 'Потерян доступ к почте, прошу привязать новый адрес через Steam.', 'approved', 'high', 'Проверено администратором. Данные сверены со Steam.', 1, '2026-11-01 12:00:00', '2026-11-01 10:00:00', '2026-11-01 12:00:00'),
  (1043, 6, 'Апелляция блокировки', 'Считаю блокировку ошибочной: статистика не изменялась вручную.', 'rejected', 'low', 'Проверено администратором. Данные сверены со Steam.', 1, '2026-11-01 12:00:00', '2026-11-01 10:00:00', '2026-11-01 12:00:00');

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `meta`, `ip_address`, `created_at`, `updated_at`) VALUES
  (1, 1, 'Вход в админ-панель', NULL, NULL, NULL, '192.168.1.14', '2026-11-02 09:12:00', '2026-11-02 09:12:00'),
  (2, 1, 'Обновлена легенда', NULL, NULL, NULL, '192.168.1.14', '2026-11-02 09:12:00', '2026-11-02 09:12:00'),
  (3, 1, 'Одобрена заявка #1044', NULL, NULL, NULL, '192.168.1.14', '2026-11-02 09:12:00', '2026-11-02 09:12:00'),
  (4, 1, 'Отклонена заявка #1043', NULL, NULL, NULL, '192.168.1.14', '2026-11-02 09:12:00', '2026-11-02 09:12:00'),
  (5, 2, 'Синхронизация Steam', NULL, NULL, NULL, '95.24.18.77', '2026-11-02 09:12:00', '2026-11-02 09:12:00'),
  (6, 8, 'Регистрация пользователя', NULL, NULL, NULL, '178.66.20.11', '2026-11-02 09:12:00', '2026-11-02 09:12:00');

-- Пароли демо-аккаунтов:
-- admin@apexstats.ru / admin123  (Argon2id)
-- player@apexstats.ru / player123 (Argon2id)
--
-- ВАЖНО: смените пароли и APP_KEY перед публикацией на VPS.
