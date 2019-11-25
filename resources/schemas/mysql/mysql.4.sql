ALTER TABLE `users`
    ADD COLUMN `remember_selector` VARCHAR(16) DEFAULT NULL,
    ADD COLUMN `remember_token` VARCHAR(256) DEFAULT NULL,
    ADD COLUMN `remember_expire` TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE `users` ADD INDEX (`remember_selector`);

