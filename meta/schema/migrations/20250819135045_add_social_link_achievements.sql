INSERT INTO `task_definitions` (
    `ctime`,
    `crand`,
    `type`,
    `code`,
    `title`,
    `description`,
    `goal_count`,
    `reward_item_id`,
    `reward_count`
) VALUES
    (NOW(6), FLOOR(RAND()*1000000000), 'achievement', 'link_discord', 'Link Discord account', 'Link your Discord account to Kickback Kingdom.', 1, 1, 1),
    (NOW(6), FLOOR(RAND()*1000000000), 'achievement', 'link_steam', 'Link Steam account', 'Link your Steam account to Kickback Kingdom.', 1, 1, 1);
