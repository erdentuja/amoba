<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$name   = trim($_POST['name'] ?? 'Néző');

if ($gameId <= 0) {
    echo json_encode(['error' => 'Hiányzó game_id']);
    exit;
}

if ($name === '') {
    $name = 'Néző';
}

$token = bin2hex(random_bytes(16));

$stmt = $pdo->prepare("
    INSERT INTO spectators (game_id, spectator_name, token, last_seen)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE spectator_name = VALUES(spectator_name), last_seen = NOW()
");
$stmt->execute([$gameId, $name, $token]);

echo json_encode(['ok' => true, 'token' => $token]);
