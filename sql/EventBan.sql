/* SQL POUR l'ENTITY EVENTBAN */

CREATE TABLE event_ban (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY user_event_unique (user_id, event_id),
    PRIMARY KEY(id),
    CONSTRAINT FK_eventban_user FOREIGN KEY (user_id) REFERENCES user(id),
    CONSTRAINT FK_eventban_event FOREIGN KEY (event_id) REFERENCES event(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
