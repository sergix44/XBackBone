ALTER TABLE `users`
    ADD COLUMN `remember_selector` VARCHAR(16),
    ADD COLUMN `remember_token` VARCHAR(256),
    ADD COLUMN `remember_expire` TIMESTAMP;

ALTER TABLE `users` ADD INDEX (`remember_selector`);

