ALTER TABLE `users` ADD COLUMN `remember_selector` VARCHAR(16);
ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(256);
ALTER TABLE `users` ADD COLUMN `remember_expire` TIMESTAMP;

CREATE INDEX IF NOT EXISTS `remember_selector_index`
  ON `users` (`remember_selector`);
