/* SQL POUR l'ENTITY EVENT*/

CREATE TABLE event (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    start_date_time DATETIME NOT NULL,
    end_date_time DATETIME NOT NULL,
    max_players INT NOT NULL,
    is_approved BOOLEAN NOT NULL DEFAULT FALSE,
    is_started BOOLEAN NOT NULL DEFAULT FALSE,
    is_finished BOOLEAN NOT NULL DEFAULT FALSE,
    points INT DEFAULT NULL,
    points_awarded BOOLEAN NOT NULL DEFAULT FALSE,
    organizer_id INT NOT NULL,
    is_admin_approved BOOLEAN NOT NULL DEFAULT FALSE,
    validated_by_id INT DEFAULT NULL,
    game VARCHAR(50) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_event_organizer FOREIGN KEY (organizer_id) REFERENCES user(id),
    CONSTRAINT fk_event_validated_by FOREIGN KEY (validated_by_id) REFERENCES user(id)
);

CREATE TABLE event_participants (
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (event_id, user_id),
    CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
