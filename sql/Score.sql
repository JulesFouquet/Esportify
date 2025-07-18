/* SQL POUR l'ENTITY SCORE */

CREATE TABLE score (
    id INT AUTO_INCREMENT NOT NULL,
    player_id INT DEFAULT NULL,
    event_id INT DEFAULT NULL,
    points INT NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT FK_score_player FOREIGN KEY (player_id) REFERENCES user(id),
    CONSTRAINT FK_score_event FOREIGN KEY (event_id) REFERENCES event(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
