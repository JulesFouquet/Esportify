/* SQL POUR l'ENTITY USER */

CREATE TABLE `user` (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL,
    pseudo VARCHAR(50) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    date_inscription DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    is_verified TINYINT(1) NOT NULL,
    points INT NOT NULL,
    UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
    PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_favorite_events (
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    INDEX IDX_user_favorite_events_user (user_id),
    INDEX IDX_user_favorite_events_event (event_id),
    PRIMARY KEY(user_id, event_id),
    CONSTRAINT FK_user_favorite_events_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE,
    CONSTRAINT FK_user_favorite_events_event FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
