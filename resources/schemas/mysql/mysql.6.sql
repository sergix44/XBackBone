CREATE TABLE IF NOT EXISTS `tags` (
  `id`        INTEGER           PRIMARY KEY      AUTO_INCREMENT,
  `name`  VARCHAR(32)       NOT NULL,
  `timestamp`    TIMESTAMP    NOT NULL         DEFAULT CURRENT_TIMESTAMP,
  INDEX (`name`)
);

CREATE TABLE IF NOT EXISTS `uploads_tags` (
    `upload_id`        INTEGER,
    `tag_id`        INTEGER,
    PRIMARY KEY (`upload_id`, `tag_id`),
    FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);