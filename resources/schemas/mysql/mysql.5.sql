ALTER TABLE `users`
    ADD COLUMN `activate_token` VARCHAR(32) DEFAULT NULL,
    ADD COLUMN `reset_token` VARCHAR(32) DEFAULT NULL,
    ADD COLUMN `current_disk_quota` BIGINT(20) NOT NULL DEFAULT 0,
    ADD COLUMN `max_disk_quota` BIGINT(20) NOT NULL DEFAULT -1;

ALTER TABLE `users` ADD INDEX (`activate_token`);
ALTER TABLE `users` ADD INDEX (`reset_token`);