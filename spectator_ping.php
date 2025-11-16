<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$token  = $_POST['token'] ?? '';

if ($gameId <= 0 || $token === '') {
    echo json_encode(['error' => 'Hiányzó adat']);
    exit;
}

$stmt = $pdo->prepare("UPDATE spectators SET last_seen = NOW() WHERE game_id = ? AND token = ?");
$stmt->execute([$gameId, $token]);

echo json_encode(['ok' => true]);
