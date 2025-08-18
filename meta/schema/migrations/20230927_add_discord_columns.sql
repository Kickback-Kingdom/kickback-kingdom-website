ALTER TABLE `account`
  ADD COLUMN `DiscordUserId` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL,
  ADD COLUMN `DiscordUsername` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  ADD UNIQUE KEY `unique_discord_user_id` (`DiscordUserId`);

