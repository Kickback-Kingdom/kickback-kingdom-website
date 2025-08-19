ALTER TABLE `account`
  ADD COLUMN `SteamUserId` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL,
  ADD COLUMN `SteamUsername` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  ADD UNIQUE KEY `unique_steam_user_id` (`SteamUserId`);

