-- Teljes amőba rendszer táblái

CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) UNIQUE NOT NULL,
    player_x_token VARCHAR(64) NOT NULL,
    player_o_token VARCHAR(64) DEFAULT NULL,
    player_x_name VARCHAR(50) DEFAULT NULL,
    player_o_name VARCHAR(50) DEFAULT NULL,
    current_turn ENUM('X','O') DEFAULT 'X',
    status ENUM('waiting','running','finished') DEFAULT 'waiting',
    winner ENUM('X','O','draw') DEFAULT NULL,
    board_size TINYINT NOT NULL DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    x TINYINT NOT NULL,
    y TINYINT NOT NULL,
    value ENUM('X','O') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_move (game_id, x, y),
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(50) UNIQUE NOT NULL,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    draws INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    sender VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS spectators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    spectator_name VARCHAR(50) NOT NULL,
    token VARCHAR(64) NOT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_spec (game_id, token)
);
