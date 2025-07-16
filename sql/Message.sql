/* SQL POUR l'ENTITY MESSAGE*/

CREATE TABLE message (
    id INT AUTO_INCREMENT NOT NULL,
    author_id INT NOT NULL,
    event_id INT NOT NULL,
    content VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    CONSTRAINT FK_message_author FOREIGN KEY (author_id) REFERENCES user(id),
    CONSTRAINT FK_message_event FOREIGN KEY (event_id) REFERENCES event(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
