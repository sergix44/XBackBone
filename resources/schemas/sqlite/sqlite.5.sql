ALTER TABLE `users` ADD COLUMN `activate_token` VARCHAR(32);
ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(32);
ALTER TABLE `users` ADD COLUMN `current_disk_quota` BIGINT NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `max_disk_quota` BIGINT NOT NULL DEFAULT -1;

CREATE INDEX IF NOT EXISTS `activate_token_index`
  ON `users` (`activate_token`);

CREATE INDEX IF NOT EXISTS `reset_token_index`
  ON `users` (`reset_token`);

