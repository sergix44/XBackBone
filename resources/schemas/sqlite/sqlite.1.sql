CREATE TABLE IF NOT EXISTS `users` (
  `id`        INTEGER           PRIMARY KEY      AUTOINCREMENT,
  `email`     VARCHAR(30)       NOT NULL,
  `username`  VARCHAR(30)       NOT NULL,
  `password`  VARCHAR(256)      NOT NULL,
  `user_code` VARCHAR(5),
  `token`     VARCHAR(256),
  `active`    BOOLEAN           NOT NULL         DEFAULT 1,
  `is_admin`  BOOLEAN           NOT NULL         DEFAULT 0,
  `registration_date` TIMESTAMP NOT NULL         DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `uploads` (
  `id`           INTEGER PRIMARY KEY           AUTOINCREMENT,
  `user_id`      INTEGER(20),
  `code`         VARCHAR(64)  NOT NULL,
  `filename`     VARCHAR(128) NOT NULL,
  `storage_path` VARCHAR(256) NOT NULL,
  `published`    BOOLEAN      NOT NULL         DEFAULT 1,
  `timestamp`    TIMESTAMP    NOT NULL         DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS `username_index`
  ON `users` (`username`);

CREATE UNIQUE INDEX IF NOT EXISTS `user_code_index`
  ON `users` (`user_code`);

CREATE UNIQUE INDEX IF NOT EXISTS `user_token`
  ON `users` (`token`);

CREATE UNIQUE INDEX IF NOT EXISTS `code_index`
  ON `uploads` (`code`);
